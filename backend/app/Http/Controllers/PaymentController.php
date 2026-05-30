<?php

namespace App\Http\Controllers;

use App\Models\BillingCheckout;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Support\InvoicePaymentFinalizer;
use App\Support\InputSanitizer;
use App\Support\RowLevelSecurity;
use App\Support\PaymentIdempotency;
use App\Support\PaymentLedger;
use App\Support\SubscriptionBillingManager;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = $request->user()
            ->payments()
            ->with(['invoice', 'client', 'latestLedgerEntry'])
            ->latest()
            ->get();

        return response()->json($payments);
    }

    public function show(Request $request, Payment $payment)
    {
        $this->ensureOwnedByUser($request, $payment);

        return response()->json($payment->load(['invoice', 'client', 'latestLedgerEntry']));
    }

    /**
     * Record a manual payment against an invoice.
     */
    public function storeManual(Request $request)
    {
        $this->ensureOperationEnabled('ops.payments_enabled', 'Payments are temporarily disabled.');

        $validated = $request->validate([
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'amount'     => 'required|numeric|min:0.01',
            'currency'   => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'notes'      => 'nullable|string|max:2000',
            'paid_at'    => 'nullable|date',
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = $request->user()->invoices()->findOrFail($validated['invoice_id']);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'manual_payment',
            $this->resolveIdempotencyKey($request, $validated),
            [
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'currency' => strtoupper($validated['currency'] ?? 'NGN'),
                'notes' => InputSanitizer::multilineText($validated['notes'] ?? null),
                'paid_at' => $validated['paid_at'] ?? null,
            ],
            $request->user()->id,
            [
                'gateway' => 'manual',
                'invoice_number' => $invoice->invoice_number,
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This payment request is still being processed. Please wait before retrying.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        $payment = $request->user()->payments()->create([
            'invoice_id' => $invoice->id,
            'client_id'  => $invoice->client_id,
            'amount'     => $validated['amount'],
            'currency'   => strtoupper($validated['currency'] ?? 'NGN'),
            'status'     => 'Pending',
            'gateway'    => 'Manual',
            'notes'      => InputSanitizer::multilineText($validated['notes'] ?? null),
            'paid_at'    => $validated['paid_at'] ?? now(),
            'gateway_reference' => 'MANUAL-' . strtoupper(Str::random(12)),
        ]);

        $ledger = app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Manual',
            'event_type' => 'captured',
            'gateway_reference' => $payment->gateway_reference,
            'dedupe_key' => sprintf('manual:%s:captured', $payment->gateway_reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'notes' => $payment->notes,
                'paid_at' => optional($payment->paid_at)->toIso8601String(),
                'source' => 'manual_entry',
            ],
            'occurred_at' => $payment->paid_at ?? now(),
        ]);

        app(InvoicePaymentFinalizer::class)->finalize($payment);

        $payment = $payment->fresh(['invoice', 'client', 'latestLedgerEntry']);

        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 201, [
            'payment' => $payment->toArray(),
            'ledger_entry' => $ledger->toArray(),
        ], 'completed', Payment::class, $payment->id);

        return response()->json($payment, 201);
    }

    /**
     * Initiate a Paystack payment link for an invoice.
     */
    public function initiatePaystack(Request $request)
    {
        $this->ensureOperationEnabled('ops.payments_enabled', 'Payments are temporarily disabled.');

        $validated = $request->validate([
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = $request->user()->invoices()->with('client')->findOrFail($validated['invoice_id']);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'invoice_gateway_paystack',
            $this->resolveIdempotencyKey($request, $validated),
            [
                'invoice_id' => $invoice->id,
                'gateway' => 'paystack',
                'amount' => (float) $invoice->total,
            ],
            $request->user()->id,
            [
                'invoice_number' => $invoice->invoice_number,
                'client_email' => $invoice->client->email,
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This payment request is still being processed. Please retry with the same idempotency key.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        $reference = 'BL-' . strtoupper(Str::random(12));
        $payment = $request->user()->payments()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $invoice->total,
            'currency' => 'NGN',
            'status' => 'Pending',
            'gateway' => 'Paystack',
            'gateway_reference' => $reference,
            'notes' => InputSanitizer::text("Invoice payment for {$invoice->invoice_number}"),
        ]);

        app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Paystack',
            'event_type' => 'intent_created',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('paystack:%s:intent_created', $reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'gateway' => 'paystack',
            ],
            'occurred_at' => now(),
        ]);

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withToken(config('services.paystack.secret_key'))
                ->post('https://api.paystack.co/transaction/initialize', [
                    'email'     => $invoice->client->email,
                    'amount'    => (int) ($invoice->total * 100), // kobo
                    'reference' => $reference,
                    'currency'  => 'NGN',
                    'metadata'  => [
                        'invoice_id' => $invoice->id,
                        'user_id'    => $request->user()->id,
                        'payment_id' => $payment->id,
                    ],
                    'callback_url' => config('app.frontend_url') . '/payments/verify?gateway=paystack',
                ]);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Payment request is still processing. Please retry with the same idempotency key.',
                'reference' => $reference,
                'idempotency_status' => 'processing',
            ], 202);
        }

        if (! $response->successful() || ! $response->json('status')) {
            app(PaymentLedger::class)->append([
                'user_id' => $request->user()->id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'gateway' => 'Paystack',
                'event_type' => 'failed',
                'gateway_reference' => $reference,
                'dedupe_key' => sprintf('paystack:%s:failed', $reference),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payload' => [
                    'reason' => 'initiation_failed',
                ],
                'occurred_at' => now(),
            ]);

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, [
                'message' => 'Failed to initiate Paystack payment',
                'reference' => $reference,
            ], 'failed', Payment::class, $payment->id);

            return response()->json(['message' => 'Failed to initiate Paystack payment'], 502);
        }

        app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Paystack',
            'event_type' => 'gateway_initiated',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('paystack:%s:gateway_initiated', $reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'authorization_url' => $response->json('data.authorization_url'),
            ],
            'occurred_at' => now(),
        ]);

        $payload = [
            'payment' => $payment->fresh(['invoice', 'client', 'latestLedgerEntry'])->toArray(),
            'authorization_url' => $response->json('data.authorization_url'),
            'reference' => $reference,
        ];

        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

        return response()->json($payload);
    }

    /**
     * Initiate a Flutterwave payment link for an invoice.
     */
    public function initiateFlutterwave(Request $request)
    {
        $this->ensureOperationEnabled('ops.payments_enabled', 'Payments are temporarily disabled.');

        $validated = $request->validate([
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = $request->user()->invoices()->with('client')->findOrFail($validated['invoice_id']);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'invoice_gateway_flutterwave',
            $this->resolveIdempotencyKey($request, $validated),
            [
                'invoice_id' => $invoice->id,
                'gateway' => 'flutterwave',
                'amount' => (float) $invoice->total,
            ],
            $request->user()->id,
            [
                'invoice_number' => $invoice->invoice_number,
                'client_email' => $invoice->client->email,
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This payment request is still being processed. Please retry with the same idempotency key.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        $reference = 'BL-' . strtoupper(Str::random(12));
        $payment = $request->user()->payments()->create([
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $invoice->total,
            'currency' => 'NGN',
            'status' => 'Pending',
            'gateway' => 'Flutterwave',
            'gateway_reference' => $reference,
            'notes' => InputSanitizer::text("Invoice payment for {$invoice->invoice_number}"),
        ]);

        app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Flutterwave',
            'event_type' => 'intent_created',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('flutterwave:%s:intent_created', $reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'gateway' => 'flutterwave',
            ],
            'occurred_at' => now(),
        ]);

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                ])->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref'          => $reference,
                    'amount'          => $invoice->total,
                    'currency'        => 'NGN',
                    'redirect_url'    => config('app.frontend_url') . '/payments/verify?gateway=flutterwave',
                    'customer'        => [
                        'email' => $invoice->client->email,
                        'name'  => $invoice->client->name,
                    ],
                    'meta' => [
                        'invoice_id' => $invoice->id,
                        'user_id'    => $request->user()->id,
                        'payment_id'  => $payment->id,
                    ],
                ]);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Payment request is still processing. Please retry with the same idempotency key.',
                'reference' => $reference,
                'idempotency_status' => 'processing',
            ], 202);
        }

        if (! $response->successful() || $response->json('status') !== 'success') {
            app(PaymentLedger::class)->append([
                'user_id' => $request->user()->id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'gateway' => 'Flutterwave',
                'event_type' => 'failed',
                'gateway_reference' => $reference,
                'dedupe_key' => sprintf('flutterwave:%s:failed', $reference),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payload' => [
                    'reason' => 'initiation_failed',
                ],
                'occurred_at' => now(),
            ]);

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, [
                'message' => 'Failed to initiate Flutterwave payment',
                'reference' => $reference,
            ], 'failed', Payment::class, $payment->id);

            return response()->json(['message' => 'Failed to initiate Flutterwave payment'], 502);
        }

        app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Flutterwave',
            'event_type' => 'gateway_initiated',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('flutterwave:%s:gateway_initiated', $reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'link' => $response->json('data.link'),
            ],
            'occurred_at' => now(),
        ]);

        $payload = [
            'payment' => $payment->fresh(['invoice', 'client', 'latestLedgerEntry'])->toArray(),
            'link' => $response->json('data.link'),
            'reference' => $reference,
        ];

        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

        return response()->json($payload);
    }

    /**
     * Verify and finalize a gateway payment after redirect.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._-]+$/'],
            'gateway'   => 'required|string|in:paystack,flutterwave',
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $idempotency = app(PaymentIdempotency::class)->reserve(
            'payment_verify_' . $request->gateway,
            $this->resolveIdempotencyKey($request),
            [
                'reference' => $request->reference,
                'gateway' => $request->gateway,
            ],
            $request->user()->id ?? null,
            [
                'reference' => $request->reference,
                'gateway' => $request->gateway,
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This payment verification is still being processed. Please retry with the same idempotency key.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        $payment = Payment::where('gateway_reference', $request->reference)->with(['invoice', 'client', 'latestLedgerEntry'])->firstOrFail();
        $this->ensureOwnedByUser($request, $payment);

        if ($payment->status === 'Completed') {
            $payment = $payment->fresh(['invoice', 'client', 'latestLedgerEntry']);

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, [
                'payment' => $payment->toArray(),
            ], 'completed', Payment::class, $payment->id);

            return response()->json($payment);
        }

        if ($request->gateway === 'paystack') {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withToken(config('services.paystack.secret_key'))
                    ->get("https://api.paystack.co/transaction/verify/{$request->reference}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Payment verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data   = $response->json('data');
            $status = $data['status'] ?? null;

            if ($status === 'success') {
                $this->appendPaymentLedgerEvent($payment, 'captured', 'paystack', [
                    'gateway_event_id' => 'paystack:transaction:' . ($data['id'] ?? $request->reference),
                    'gateway_reference' => $request->reference,
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $status,
                    'source' => 'verify',
                ]);
            } else {
                $this->appendPaymentLedgerEvent($payment, 'failed', 'paystack', [
                    'gateway_event_id' => 'paystack:verify:' . $request->reference,
                    'gateway_reference' => $request->reference,
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $status,
                    'source' => 'verify',
                ]);
            }
        } elseif ($request->gateway === 'flutterwave') {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                    ])->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$request->reference}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Payment verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data   = $response->json('data');
            $status = $data['status'] ?? null;

            if ($status === 'successful') {
                $this->appendPaymentLedgerEvent($payment, 'captured', 'flutterwave', [
                    'gateway_event_id' => 'flutterwave:transaction:' . ($data['id'] ?? $request->reference),
                    'gateway_reference' => $request->reference,
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $status,
                    'source' => 'verify',
                ]);
            } else {
                $this->appendPaymentLedgerEvent($payment, 'failed', 'flutterwave', [
                    'gateway_event_id' => 'flutterwave:verify:' . $request->reference,
                    'gateway_reference' => $request->reference,
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $status,
                    'source' => 'verify',
                ]);
            }
        }

        // Mark invoice as Paid if fully covered
        if ($payment->fresh(['latestLedgerEntry'])->status === 'Completed') {
            app(InvoicePaymentFinalizer::class)->finalize($payment->fresh(['invoice', 'client', 'latestLedgerEntry']));
        }

        $payment = $payment->fresh(['invoice', 'client', 'latestLedgerEntry']);
        $payload = $payment->toArray();
        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, [
            'payment' => $payload,
        ], 'completed', Payment::class, $payment->id);

        return response()->json($payment);
    }

    /**
     * Paystack webhook handler (no auth middleware).
     */
    public function paystackWebhook(Request $request)
    {
        $this->ensureOperationEnabled('ops.webhooks_enabled', 'Payment webhooks are temporarily disabled.');

        $signature = $request->header('x-paystack-signature');
        if (! is_string($signature) || $signature === '') {
            return response()->json(['message' => 'Invalid signature'], 401);
        }
        $computed  = hash_hmac('sha512', $request->getContent(), config('services.paystack.secret_key'));

        if (! hash_equals($computed, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->json('event');
        $data  = $request->json('data');
        if (! is_string($event) || ! is_array($data)) {
            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $this->applyWebhookDatabaseContext($data);

        if ($event === 'charge.success' && $this->handleSubscriptionWebhook('paystack', $data)) {
            return response()->json(['message' => 'OK']);
        }

        if ($event === 'charge.success' && isset($data['reference'])) {
            $payment = Payment::query()
                ->where('gateway_reference', $data['reference'])
                ->with(['invoice', 'client', 'latestLedgerEntry'])
                ->first();

            if ($payment) {
                $this->appendPaymentLedgerEvent($payment, 'captured', 'paystack', [
                    'gateway_event_id' => $this->resolveWebhookEventId('paystack', $event, $data),
                    'gateway_reference' => $data['reference'],
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? 'success',
                    'source' => 'webhook',
                    'event' => $event,
                ]);

                app(InvoicePaymentFinalizer::class)->finalize($payment->fresh(['invoice', 'client', 'latestLedgerEntry']));
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Flutterwave webhook handler (no auth middleware).
     */
    public function flutterwaveWebhook(Request $request)
    {
        $this->ensureOperationEnabled('ops.webhooks_enabled', 'Payment webhooks are temporarily disabled.');

        $hash = $request->header('verif-hash') ?? $request->header('flutterwave-signature');
        if (! is_string($hash) || $hash === '' || $hash !== config('services.flutterwave.webhook_hash')) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data   = $request->json('data');
        $status = is_array($data) ? ($data['status'] ?? null) : null;
        $event  = (string) ($request->json('event') ?? 'charge.completed');

        if (is_array($data)) {
            $this->applyWebhookDatabaseContext($data);
        }

        if ($status === 'successful' && $this->handleSubscriptionWebhook('flutterwave', $data)) {
            return response()->json(['message' => 'OK']);
        }

        if ($status === 'successful' && is_array($data) && isset($data['tx_ref'])) {
            $payment = Payment::query()
                ->where('gateway_reference', $data['tx_ref'])
                ->with(['invoice', 'client', 'latestLedgerEntry'])
                ->first();

            if ($payment) {
                $this->appendPaymentLedgerEvent($payment, 'captured', 'flutterwave', [
                    'gateway_event_id' => $this->resolveWebhookEventId('flutterwave', $event, $data),
                    'gateway_reference' => $data['tx_ref'],
                    'transaction_id' => $data['id'] ?? null,
                    'status' => $status,
                    'source' => 'webhook',
                    'event' => $event,
                ]);

                app(InvoicePaymentFinalizer::class)->finalize($payment->fresh(['invoice', 'client', 'latestLedgerEntry']));
            }
        }

        return response()->json(['message' => 'OK']);
    }

    private function handleSubscriptionWebhook(string $gateway, array $data): bool
    {
        $reference = data_get($data, 'reference') ?? data_get($data, 'tx_ref');
        $metadata = is_array(data_get($data, 'metadata'))
            ? data_get($data, 'metadata')
            : (is_array(data_get($data, 'meta')) ? data_get($data, 'meta') : []);

        if (is_numeric(data_get($metadata, 'user_id'))) {
            RowLevelSecurity::setContext([
                'app.user_id' => (string) (int) data_get($metadata, 'user_id'),
                'app.user_role' => 'owner',
                'app.access_mode' => 'authenticated',
                'app.public_access_token' => '',
            ]);
        }

        $checkout = $reference !== null
            ? BillingCheckout::query()->where('reference', $reference)->with('plan', 'user')->first()
            : null;

        $kind = (string) data_get($metadata, 'kind', data_get($checkout?->metadata, 'kind', ''));
        if ($reference === null || ! str_starts_with($kind, 'subscription')) {
            return false;
        }

        if (! $checkout) {
            $userId = data_get($metadata, 'user_id');
            $planCode = data_get($metadata, 'plan_code');

            if (! $userId || ! $planCode) {
                return false;
            }

            $user = User::query()->find($userId);
            $plan = Plan::query()->where('code', $planCode)->first();

            if (! $user || ! $plan) {
                return false;
            }

            $checkout = app(SubscriptionBillingManager::class)->recordCheckout(
                $user,
                $plan,
                $gateway,
                (string) $reference,
                $gateway === 'paystack'
                    ? (int) round(((int) data_get($data, 'amount', 0)) / 100)
                    : (int) data_get($data, 'amount', 0),
                (string) data_get($metadata, 'billing_interval', 'monthly'),
                array_merge($metadata, [
                    'kind' => $kind,
                ]),
                'pending'
            );
            $checkout->load('plan', 'user');
        }

        if ($checkout->status === 'paid') {
            return true;
        }

        $transactionId = data_get($data, 'id');
        $authorizationData = $gateway === 'paystack'
            ? (is_array(data_get($data, 'authorization')) ? data_get($data, 'authorization') : [])
            : (is_array(data_get($data, 'card')) ? data_get($data, 'card') : []);

        $status = $gateway === 'paystack'
            ? (data_get($data, 'status') === 'success')
            : (data_get($data, 'status') === 'successful');

        $ledger = app(PaymentLedger::class)->append([
            'user_id' => $checkout->user_id,
            'billing_checkout_id' => $checkout->id,
            'subscription_id' => data_get($checkout->metadata, 'subscription_id'),
            'gateway' => $gateway,
            'event_type' => $status ? 'captured' : 'failed',
            'gateway_event_id' => $this->resolveWebhookEventId($gateway, (string) data_get($data, 'event', 'charge.success'), $data),
            'gateway_reference' => (string) $reference,
            'dedupe_key' => sprintf('%s:%s:%s', $gateway, $reference, $status ? 'captured' : 'failed'),
            'amount' => $checkout->amount_ngn,
            'currency' => 'NGN',
            'payload' => array_merge($data, [
                'kind' => $kind,
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
            ]),
            'occurred_at' => now(),
        ]);

        if (! $status) {
            $checkout->update(['status' => 'failed']);

            return true;
        }

        $checkout->update(['status' => 'paid']);

        $subscription = app(SubscriptionBillingManager::class)->activateOrRenew(
            $checkout->user,
            $checkout->plan,
            $gateway,
            $checkout->billing_interval ?? (string) data_get($metadata, 'billing_interval', 'monthly'),
            (string) $reference,
            is_null($transactionId) ? null : (string) $transactionId,
            array_merge($checkout->metadata ?? [], [
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
                'payment_token' => data_get($authorizationData, 'authorization_code') ?? data_get($authorizationData, 'token'),
                'payment_customer_code' => data_get($authorizationData, 'customer_code'),
                'payment_reusable' => data_get($authorizationData, 'reusable'),
                'payment_method' => $gateway,
                'webhook_reference' => $reference,
            ])
        );

        app(PaymentLedger::class)->append([
            'user_id' => $checkout->user_id,
            'billing_checkout_id' => $checkout->id,
            'subscription_id' => $subscription->id,
            'gateway' => $gateway,
            'event_type' => 'subscription_renewed',
            'gateway_reference' => (string) $reference,
            'dedupe_key' => sprintf('%s:%s:subscription_renewed', $gateway, $reference),
            'amount' => $checkout->amount_ngn,
            'currency' => 'NGN',
            'payload' => array_merge($checkout->metadata ?? [], [
                'subscription_id' => $subscription->id,
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
                'ledger_entry_id' => $ledger->id,
            ]),
            'occurred_at' => now(),
        ]);

        $subscription->load('plan');

        return true;
    }

    private function applyWebhookDatabaseContext(array $data): void
    {
        $metadata = is_array(data_get($data, 'metadata'))
            ? data_get($data, 'metadata')
            : (is_array(data_get($data, 'meta')) ? data_get($data, 'meta') : []);

        if (is_numeric(data_get($metadata, 'user_id'))) {
            RowLevelSecurity::setContext([
                'app.user_id' => (string) (int) data_get($metadata, 'user_id'),
                'app.user_role' => 'owner',
                'app.access_mode' => 'authenticated',
                'app.public_access_token' => '',
            ]);

            return;
        }

        if (is_string(data_get($metadata, 'public_token')) && trim((string) data_get($metadata, 'public_token')) !== '') {
            RowLevelSecurity::setContext([
                'app.user_id' => '',
                'app.user_role' => '',
                'app.access_mode' => 'public',
                'app.public_access_token' => trim((string) data_get($metadata, 'public_token')),
            ]);
        }
    }

    private function appendPaymentLedgerEvent(Payment $payment, string $eventType, string $gateway, array $payload = []): \App\Models\PaymentLedgerEntry
    {
        return app(PaymentLedger::class)->append(array_merge([
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'gateway' => ucfirst($gateway),
            'event_type' => $eventType,
            'gateway_reference' => $payment->gateway_reference,
            'dedupe_key' => sprintf('%s:%s:%s', $gateway, $payment->gateway_reference, $eventType),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'occurred_at' => now(),
        ], $payload));
    }

    private function resolveWebhookEventId(string $gateway, string $event, array $data): string
    {
        $eventId = data_get($data, 'id');

        if ($eventId === null || $eventId === '') {
            throw ValidationException::withMessages([
                'event_id' => 'Webhook payload is missing a valid event identifier.',
            ]);
        }

        return sprintf('%s:%s:%s', $gateway, $event, (string) $eventId);
    }

    private function resolveIdempotencyKey(Request $request, array $validated = []): ?string
    {
        $header = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
        $body = $validated['idempotency_key'] ?? $request->input('idempotency_key');
        $key = is_string($header) && trim($header) !== '' ? $header : (is_string($body) ? $body : null);

        return is_string($key) ? trim($key) : null;
    }
}
