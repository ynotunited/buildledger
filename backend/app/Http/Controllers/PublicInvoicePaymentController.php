<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicInvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\PaymentIdempotency;
use App\Support\PaymentLedger;
use App\Support\InputSanitizer;
use App\Support\InvoicePaymentFinalizer;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicInvoicePaymentController extends Controller
{
    public function show(string $token)
    {
        $invoice = $this->findActiveInvoice($token);

        return response()->json((new PublicInvoiceResource($invoice))->resolve());
    }

    public function initiate(Request $request, string $token)
    {
        $this->ensureOperationEnabled('ops.payments_enabled', 'Payments are temporarily disabled.');

        $validated = $request->validate([
            'gateway' => ['required', Rule::in(['paystack', 'flutterwave'])],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = $this->findActiveInvoice($token);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'public_invoice_pay_' . $validated['gateway'],
            $this->resolveIdempotencyKey($request, $validated),
            [
                'invoice_id' => $invoice->id,
                'gateway' => $validated['gateway'],
                'token' => $token,
            ],
            $invoice->user_id,
            [
                'invoice_id' => $invoice->id,
                'token' => $token,
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

        if ($invoice->status === 'Paid') {
            $payload = ['message' => 'This invoice has already been paid.'];
            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 409, $payload, 'completed', Invoice::class, $invoice->id);

            return response()->json($payload, 409);
        }

        $reference = 'INV-' . strtoupper(Str::random(14));
        $checkoutUrl = rtrim(config('app.frontend_url'), '/') . "/pay/{$token}/verify?gateway={$validated['gateway']}";

        $payment = $this->createPendingPayment($invoice, $validated['gateway'], $reference);
        app(PaymentLedger::class)->append([
            'user_id' => $invoice->user_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => ucfirst($validated['gateway']),
            'event_type' => 'intent_created',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('%s:%s:intent_created', $validated['gateway'], $reference),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'kind' => 'public_invoice',
                'invoice_id' => $invoice->id,
                'public_token' => $token,
            ],
            'occurred_at' => now(),
        ]);

        try {
            if ($validated['gateway'] === 'paystack') {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withToken(config('services.paystack.secret_key'))
                    ->post('https://api.paystack.co/transaction/initialize', [
                        'email' => $invoice->client->email,
                        'amount' => (int) ($invoice->total * 100),
                        'reference' => $reference,
                        'currency' => 'NGN',
                        'metadata' => [
                            'kind' => 'public_invoice',
                            'invoice_id' => $invoice->id,
                            'user_id' => $invoice->user_id,
                            'public_token' => $token,
                            'payment_id' => $payment->id,
                        ],
                        'callback_url' => $checkoutUrl,
                    ]);

                if (! $response->successful() || ! $response->json('status')) {
                    app(PaymentLedger::class)->append([
                        'user_id' => $invoice->user_id,
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'gateway' => 'Paystack',
                        'event_type' => 'failed',
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('paystack:%s:failed', $reference),
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'payload' => ['reason' => 'initiation_failed'],
                        'occurred_at' => now(),
                    ]);

                    $payload = ['message' => 'Failed to initiate invoice payment.'];
                    app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, $payload, 'failed', Payment::class, $payment->id);

                    return response()->json($payload, 502);
                }

                app(PaymentLedger::class)->append([
                    'user_id' => $invoice->user_id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'gateway' => 'Paystack',
                    'event_type' => 'gateway_initiated',
                    'gateway_reference' => $reference,
                    'dedupe_key' => sprintf('paystack:%s:gateway_initiated', $reference),
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payload' => ['authorization_url' => $response->json('data.authorization_url')],
                    'occurred_at' => now(),
                ]);

                $payload = [
                    'authorization_url' => $response->json('data.authorization_url'),
                    'reference' => $reference,
                ];

                app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

                return response()->json($payload);
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                ])->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $reference,
                    'amount' => $invoice->total,
                    'currency' => 'NGN',
                    'redirect_url' => $checkoutUrl,
                    'customer' => [
                        'email' => $invoice->client->email,
                        'name' => $invoice->client->name,
                    ],
                    'meta' => [
                        'kind' => 'public_invoice',
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'public_token' => $token,
                        'payment_id' => $payment->id,
                    ],
                ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
                app(PaymentLedger::class)->append([
                    'user_id' => $invoice->user_id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'gateway' => 'Flutterwave',
                    'event_type' => 'failed',
                    'gateway_reference' => $reference,
                    'dedupe_key' => sprintf('flutterwave:%s:failed', $reference),
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'payload' => ['reason' => 'initiation_failed'],
                    'occurred_at' => now(),
                ]);

                $payload = ['message' => 'Failed to initiate invoice payment.'];
                app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, $payload, 'failed', Payment::class, $payment->id);

                return response()->json($payload, 502);
            }

            app(PaymentLedger::class)->append([
                'user_id' => $invoice->user_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'gateway' => 'Flutterwave',
                'event_type' => 'gateway_initiated',
                'gateway_reference' => $reference,
                'dedupe_key' => sprintf('flutterwave:%s:gateway_initiated', $reference),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payload' => ['link' => $response->json('data.link')],
                'occurred_at' => now(),
            ]);

            $payload = [
                'link' => $response->json('data.link'),
                'reference' => $reference,
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

            return response()->json($payload);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Invoice payment is still processing. Please retry with the same idempotency key.',
                'reference' => $reference,
                'idempotency_status' => 'processing',
            ], 202);
        }
    }

    public function verify(Request $request, string $token)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._-]+$/'],
            'gateway' => ['required', Rule::in(['paystack', 'flutterwave'])],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = $this->findActiveInvoice($token);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'public_invoice_verify_' . $validated['gateway'],
            $this->resolveIdempotencyKey($request, $validated),
            [
                'invoice_id' => $invoice->id,
                'reference' => $validated['reference'],
                'gateway' => $validated['gateway'],
            ],
            $invoice->user_id,
            [
                'invoice_id' => $invoice->id,
                'reference' => $validated['reference'],
                'token' => $token,
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

        $payment = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('gateway_reference', $validated['reference'])
            ->with(['invoice', 'client', 'latestLedgerEntry'])
            ->firstOrFail();

        if ($payment->status === 'Completed') {
            $payment = $payment->fresh(['invoice', 'client', 'latestLedgerEntry']);
            app(InvoicePaymentFinalizer::class)->finalize($payment->fresh(['invoice', 'client', 'latestLedgerEntry']));

            $payload = [
                'message' => 'Payment already verified.',
                'invoice' => (new PublicInvoiceResource($invoice->fresh(['client', 'company', 'items'])))->resolve(),
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

            return response()->json($payload);
        }

        $verified = false;
        $transactionId = null;

        if ($validated['gateway'] === 'paystack') {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withToken(config('services.paystack.secret_key'))
                    ->get("https://api.paystack.co/transaction/verify/{$validated['reference']}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Payment verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data = $response->json('data');
            $verified = ($data['status'] ?? null) === 'success';
            $transactionId = $data['id'] ?? null;
        } else {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                    ])->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$validated['reference']}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Payment verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data = $response->json('data');
            $verified = ($data['status'] ?? null) === 'successful';
            $transactionId = $data['id'] ?? null;
        }

        if (! $verified) {
            app(PaymentLedger::class)->append([
                'user_id' => $invoice->user_id,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'gateway' => ucfirst($validated['gateway']),
                'event_type' => 'failed',
                'gateway_reference' => $validated['reference'],
                'dedupe_key' => sprintf('%s:%s:failed', $validated['gateway'], $validated['reference']),
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payload' => [
                    'source' => 'verify',
                    'transaction_id' => $transactionId,
                ],
                'occurred_at' => now(),
            ]);

            $payload = ['message' => 'Payment could not be verified.'];
            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 422, $payload, 'failed', Payment::class, $payment->id);

            return response()->json($payload, 422);
        }

        app(PaymentLedger::class)->append([
            'user_id' => $invoice->user_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => ucfirst($validated['gateway']),
            'event_type' => 'captured',
            'gateway_event_id' => sprintf('%s:verify:%s', $validated['gateway'], $transactionId ?? $validated['reference']),
            'gateway_reference' => $validated['reference'],
            'dedupe_key' => sprintf('%s:%s:captured', $validated['gateway'], $validated['reference']),
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payload' => [
                'source' => 'verify',
                'transaction_id' => $transactionId,
            ],
            'occurred_at' => now(),
        ]);

        app(InvoicePaymentFinalizer::class)->finalize($payment->fresh(['invoice', 'client', 'latestLedgerEntry']));

        $payload = [
            'message' => 'Payment verified successfully.',
            'invoice' => (new PublicInvoiceResource($invoice->fresh(['client', 'company', 'items'])))->resolve(),
        ];

        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, $payment->id);

        return response()->json($payload);
    }

    private function findActiveInvoice(string $token): Invoice
    {
        $invoice = Invoice::query()
            ->where('public_payment_token', $token)
            ->with(['client', 'company', 'items', 'user.company'])
            ->firstOrFail();

        if (! $invoice->hasActivePaymentLink()) {
            abort(410, 'This payment link is invalid or expired.');
        }

        return $invoice;
    }

    private function createPendingPayment(Invoice $invoice, string $gateway, string $reference): Payment
    {
        return Payment::create([
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'client_id' => $invoice->client_id,
            'amount' => $invoice->total,
            'currency' => 'NGN',
            'status' => 'Pending',
            'gateway' => ucfirst($gateway),
            'gateway_reference' => $reference,
            'notes' => InputSanitizer::text("Public invoice payment for {$invoice->invoice_number}"),
        ]);
    }

    private function resolveIdempotencyKey(Request $request, array $validated = []): ?string
    {
        $header = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
        $body = $validated['idempotency_key'] ?? $request->input('idempotency_key');
        $key = is_string($header) && trim($header) !== '' ? $header : (is_string($body) ? $body : null);

        return is_string($key) ? trim($key) : null;
    }
}
