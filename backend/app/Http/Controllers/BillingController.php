<?php

namespace App\Http\Controllers;

use App\Http\Resources\PlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\BillingCheckout;
use App\Models\Payment;
use App\Models\Plan;
use App\Support\PaymentIdempotency;
use App\Support\PaymentLedger;
use App\Support\SubscriptionBillingManager;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $plans = Plan::query()->where('is_active', true)->orderBy('sort_order')->get();
        $subscription = $user->activeSubscription()->with('plan')->first();
        $currentPlan = $user->currentPlan();

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'current_plan' => $currentPlan ? new PlanResource($currentPlan) : null,
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'trial_ends_at' => $user->trial_ends_at,
        ]);
    }

    public function cancel(Request $request)
    {
        $subscription = $request->user()
            ->subscriptions()
            ->where('status', 'active')
            ->with('plan')
            ->latest('id')
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'No active subscription to cancel.',
                'subscription' => null,
            ]);
        }

        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'expires_at' => now(),
            'current_period_ends_at' => now(),
        ]);

        return response()->json([
            'message' => 'Subscription cancelled successfully.',
            'subscription' => new SubscriptionResource($subscription->fresh()->load('plan')),
        ]);
    }

    public function checkout(Request $request)
    {
        $this->ensureOperationEnabled('ops.payments_enabled', 'Payments are temporarily disabled.');

        $validated = $request->validate([
            'plan_code' => ['required', Rule::exists('plans', 'code')->where(fn ($query) => $query->where('is_active', true))],
            'gateway' => ['required', Rule::in(['paystack', 'flutterwave'])],
            'billing_interval' => ['required', Rule::in(['monthly', 'annual'])],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $plan = Plan::query()->where('code', $validated['plan_code'])->firstOrFail();
        $user = $request->user();
        $manager = app(SubscriptionBillingManager::class);
        $billingInterval = $manager->normalizeInterval($validated['billing_interval']);
        $idempotency = app(PaymentIdempotency::class)->reserve(
            'billing_checkout_' . $validated['gateway'],
            $this->resolveIdempotencyKey($request, $validated),
            [
                'plan_code' => $plan->code,
                'gateway' => $validated['gateway'],
                'billing_interval' => $billingInterval,
            ],
            $user->id,
            [
                'plan_code' => $plan->code,
                'billing_interval' => $billingInterval,
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This billing checkout is still being processed. Please retry with the same idempotency key.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        if ($plan->price_ngn <= 0) {
            if ($user->trial_ends_at && $user->trial_ends_at->isPast()) {
                $payload = [
                    'message' => 'Your free trial has ended. Please choose a paid plan to continue.',
                ];

                app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 422, $payload, 'failed');

                return response()->json($payload, 422);
            }

            $activeSubscription = $user->subscriptions()->where('status', 'active')->latest('id')->first();
            if ($activeSubscription) {
                $activeSubscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'expires_at' => now(),
                    'current_period_ends_at' => now(),
                ]);
            }

            $manager->startTrial($user);

            $payload = [
                'message' => 'Starter trial activated.',
                'trial_ends_at' => $user->fresh()->trial_ends_at,
                'current_plan' => $user->fresh()->currentPlan() ? new PlanResource($user->fresh()->currentPlan()) : null,
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed');

            return response()->json($payload);
        }

        $reference = 'SUB-' . strtoupper(Str::random(14));
        $amountNgN = $manager->priceForInterval($plan, $billingInterval);
        $prorationCredit = $manager->calculateProrationCredit($user, $plan, $billingInterval);
        $amountDue = max(0, $amountNgN - $prorationCredit);
        $activeSubscription = $user->activeSubscription()->with('plan')->first();
        $checkout = BillingCheckout::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'gateway' => $validated['gateway'],
            'reference' => $reference,
            'amount_ngn' => $amountDue,
            'billing_interval' => $billingInterval,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'kind' => $activeSubscription ? 'subscription_change' : 'subscription',
                'plan_code' => $plan->code,
                'billing_interval' => $billingInterval,
                'base_amount_ngn' => $amountNgN,
                'proration_credit_ngn' => $prorationCredit,
                'current_subscription_id' => $activeSubscription?->id,
                'current_plan_code' => $activeSubscription?->plan?->code,
                'current_billing_interval' => $activeSubscription?->billing_interval,
            ],
        ]);

        app(PaymentLedger::class)->append([
            'user_id' => $user->id,
            'billing_checkout_id' => $checkout->id,
            'subscription_id' => $activeSubscription?->id,
            'gateway' => $validated['gateway'],
            'event_type' => 'intent_created',
            'gateway_reference' => $reference,
            'dedupe_key' => sprintf('%s:%s:intent_created', $validated['gateway'], $reference),
            'amount' => $amountDue,
            'currency' => 'NGN',
            'payload' => [
                'plan_code' => $plan->code,
                'billing_interval' => $billingInterval,
                'proration_credit_ngn' => $prorationCredit,
                'checkout_id' => $checkout->id,
            ],
            'occurred_at' => now(),
        ]);

        if ($amountDue === 0) {
            $subscription = $manager->activateOrRenew(
                $user,
                $plan,
                'internal',
                $billingInterval,
                $reference,
                null,
                [
                    'kind' => 'subscription_change',
                    'checkout_id' => $checkout->id,
                    'proration_credit_ngn' => $prorationCredit,
                    'amount_paid_ngn' => 0,
                ]
            );

            $checkout->update(['status' => 'paid']);
            app(PaymentLedger::class)->append([
                'user_id' => $user->id,
                'billing_checkout_id' => $checkout->id,
                'subscription_id' => $subscription->id,
                'gateway' => 'internal',
                'event_type' => 'subscription_renewed',
                'gateway_reference' => $reference,
                'dedupe_key' => sprintf('internal:%s:subscription_renewed', $reference),
                'amount' => 0,
                'currency' => 'NGN',
                'payload' => [
                    'plan_code' => $plan->code,
                    'billing_interval' => $billingInterval,
                    'proration_credit_ngn' => $prorationCredit,
                    'checkout_id' => $checkout->id,
                ],
                'occurred_at' => now(),
            ]);

            $payload = [
                'message' => 'Subscription updated successfully.',
                'subscription' => new SubscriptionResource($subscription->load('plan')),
                'checkout' => $checkout->fresh(),
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', Payment::class, null, [
                'checkout_id' => $checkout->id,
            ]);

            return response()->json($payload);
        }

        try {
            if ($validated['gateway'] === 'paystack') {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withToken(config('services.paystack.secret_key'))
                    ->post('https://api.paystack.co/transaction/initialize', [
                        'email' => $user->email,
                        'amount' => $amountDue * 100,
                        'reference' => $reference,
                        'currency' => 'NGN',
                        'metadata' => [
                            'kind' => 'subscription',
                            'checkout_id' => $checkout->id,
                            'user_id' => $user->id,
                            'plan_code' => $plan->code,
                            'billing_interval' => $billingInterval,
                            'base_amount_ngn' => $amountNgN,
                            'proration_credit_ngn' => $prorationCredit,
                        ],
                        'callback_url' => config('app.frontend_url') . '/billing/verify?gateway=paystack',
                    ]);

                if (! $response->successful() || ! $response->json('status')) {
                    $checkout->update(['status' => 'failed']);
                    app(PaymentLedger::class)->append([
                        'user_id' => $user->id,
                        'billing_checkout_id' => $checkout->id,
                        'gateway' => 'paystack',
                        'event_type' => 'failed',
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('paystack:%s:subscription_failed', $reference),
                        'amount' => $amountDue,
                        'currency' => 'NGN',
                        'payload' => ['reason' => 'initiation_failed'],
                        'occurred_at' => now(),
                    ]);
                    $payload = ['message' => 'Failed to initiate subscription payment.'];
                    app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, $payload, 'failed');
                    return response()->json($payload, 502);
                }

                app(PaymentLedger::class)->append([
                    'user_id' => $user->id,
                    'billing_checkout_id' => $checkout->id,
                    'gateway' => 'paystack',
                    'event_type' => 'gateway_initiated',
                    'gateway_reference' => $reference,
                    'dedupe_key' => sprintf('paystack:%s:subscription_gateway_initiated', $reference),
                    'amount' => $amountDue,
                    'currency' => 'NGN',
                    'payload' => ['authorization_url' => $response->json('data.authorization_url')],
                    'occurred_at' => now(),
                ]);

                $payload = [
                    'authorization_url' => $response->json('data.authorization_url'),
                    'reference' => $reference,
                    'amount_due_ngn' => $amountDue,
                    'proration_credit_ngn' => $prorationCredit,
                ];

                app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', BillingCheckout::class, $checkout->id);

                return response()->json($payload);
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                ])->post('https://api.flutterwave.com/v3/payments', [
                    'tx_ref' => $reference,
                    'amount' => $amountDue,
                    'currency' => 'NGN',
                    'redirect_url' => config('app.frontend_url') . '/billing/verify?gateway=flutterwave',
                    'customer' => [
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                    'meta' => [
                        'kind' => 'subscription',
                        'checkout_id' => $checkout->id,
                        'user_id' => $user->id,
                        'plan_code' => $plan->code,
                        'billing_interval' => $billingInterval,
                        'base_amount_ngn' => $amountNgN,
                        'proration_credit_ngn' => $prorationCredit,
                    ],
                ]);

            if (! $response->successful() || $response->json('status') !== 'success') {
                $checkout->update(['status' => 'failed']);
                app(PaymentLedger::class)->append([
                    'user_id' => $user->id,
                    'billing_checkout_id' => $checkout->id,
                    'gateway' => 'flutterwave',
                    'event_type' => 'failed',
                    'gateway_reference' => $reference,
                    'dedupe_key' => sprintf('flutterwave:%s:subscription_failed', $reference),
                    'amount' => $amountDue,
                    'currency' => 'NGN',
                    'payload' => ['reason' => 'initiation_failed'],
                    'occurred_at' => now(),
                ]);
                $payload = ['message' => 'Failed to initiate subscription payment.'];
                app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 502, $payload, 'failed');
                return response()->json($payload, 502);
            }

            app(PaymentLedger::class)->append([
                'user_id' => $user->id,
                'billing_checkout_id' => $checkout->id,
                'gateway' => 'flutterwave',
                'event_type' => 'gateway_initiated',
                'gateway_reference' => $reference,
                'dedupe_key' => sprintf('flutterwave:%s:subscription_gateway_initiated', $reference),
                'amount' => $amountDue,
                'currency' => 'NGN',
                'payload' => ['link' => $response->json('data.link')],
                'occurred_at' => now(),
            ]);

            $payload = [
                'authorization_url' => $response->json('data.link'),
                'reference' => $reference,
                'amount_due_ngn' => $amountDue,
                'proration_credit_ngn' => $prorationCredit,
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', BillingCheckout::class, $checkout->id);

            return response()->json($payload);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => 'Subscription checkout is still processing. Please retry with the same idempotency key.',
                'reference' => $reference,
                'idempotency_status' => 'processing',
            ], 202);
        }
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:150', 'regex:/^[A-Za-z0-9._-]+$/'],
            'gateway' => ['required', Rule::in(['paystack', 'flutterwave'])],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $idempotency = app(PaymentIdempotency::class)->reserve(
            'billing_verify_' . $validated['gateway'],
            $this->resolveIdempotencyKey($request, $validated),
            [
                'reference' => $validated['reference'],
                'gateway' => $validated['gateway'],
            ],
            $request->user()->id,
            [
                'reference' => $validated['reference'],
                'gateway' => $validated['gateway'],
            ]
        );

        if ($idempotency['state'] === 'cached') {
            return response()->json($idempotency['cached_response'], $idempotency['record']->response_status ?? 200);
        }

        if ($idempotency['state'] === 'processing') {
            return response()->json([
                'message' => 'This billing verification is still being processed. Please retry with the same idempotency key.',
                'idempotency_status' => 'processing',
            ], 202);
        }

        $checkout = BillingCheckout::query()
            ->where('reference', $validated['reference'])
            ->where('user_id', $request->user()->id)
            ->with('plan')
            ->firstOrFail();

        if ($checkout->status === 'paid') {
            $subscription = $request->user()->activeSubscription()->with('plan')->first();

            $payload = [
                'message' => 'Subscription already active.',
                'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            ];

            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', BillingCheckout::class, $checkout->id);

            return response()->json($payload);
        }

        $verified = false;
        $transactionId = null;
        $authorizationData = [];

        if ($validated['gateway'] === 'paystack') {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withToken(config('services.paystack.secret_key'))
                    ->get("https://api.paystack.co/transaction/verify/{$validated['reference']}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Subscription verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data = $response->json('data');
            $verified = ($data['status'] ?? null) === 'success';
            $transactionId = $data['id'] ?? null;
            $authorizationData = is_array($data['authorization'] ?? null) ? $data['authorization'] : [];
        } else {
            try {
                $response = Http::timeout(30)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
                    ])->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$validated['reference']}");
            } catch (ConnectionException $exception) {
                return response()->json([
                    'message' => 'Subscription verification is still processing. Please retry with the same idempotency key.',
                    'idempotency_status' => 'processing',
                ], 202);
            }

            $data = $response->json('data');
            $verified = ($data['status'] ?? null) === 'successful';
            $transactionId = $data['id'] ?? null;
            $authorizationData = is_array($data['card'] ?? null) ? $data['card'] : [];
        }

        $ledger = app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'billing_checkout_id' => $checkout->id,
            'subscription_id' => data_get($checkout->metadata, 'subscription_id'),
            'gateway' => $validated['gateway'],
            'event_type' => $verified ? 'captured' : 'failed',
            'gateway_event_id' => $this->resolveWebhookEventId($validated['gateway'], 'verify', [
                'id' => $transactionId ?? $validated['reference'],
            ]),
            'gateway_reference' => $validated['reference'],
            'dedupe_key' => sprintf('%s:%s:%s', $validated['gateway'], $validated['reference'], $verified ? 'captured' : 'failed'),
            'amount' => $checkout->amount_ngn,
            'currency' => 'NGN',
            'payload' => [
                'status' => $verified ? 'verified' : 'failed',
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
                'transaction_id' => $transactionId,
            ],
            'occurred_at' => now(),
        ]);

        if (! $verified) {
            $checkout->update(['status' => 'failed']);

            $payload = ['message' => 'Subscription payment could not be verified.'];
            app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 422, $payload, 'failed', BillingCheckout::class, $checkout->id);

            return response()->json($payload, 422);
        }

        $checkout->update(['status' => 'paid']);
        $subscription = app(SubscriptionBillingManager::class)->activateOrRenew(
            $request->user(),
            $checkout->plan,
            $validated['gateway'],
            $checkout->billing_interval ?? 'monthly',
            $validated['reference'],
            is_null($transactionId) ? null : (string) $transactionId,
            array_merge($checkout->metadata ?? [], [
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
                'proration_credit_ngn' => data_get($checkout->metadata, 'proration_credit_ngn', 0),
                'amount_paid_ngn' => $checkout->amount_ngn,
                'payment_token' => data_get($authorizationData, 'authorization_code') ?? data_get($authorizationData, 'token'),
                'payment_customer_code' => data_get($authorizationData, 'customer_code'),
                'payment_reusable' => data_get($authorizationData, 'reusable'),
                'webhook_reference' => $validated['reference'],
            ])
        );

        app(PaymentLedger::class)->append([
            'user_id' => $request->user()->id,
            'billing_checkout_id' => $checkout->id,
            'subscription_id' => $subscription->id,
            'gateway' => $validated['gateway'],
            'event_type' => 'subscription_renewed',
            'gateway_reference' => $validated['reference'],
            'dedupe_key' => sprintf('%s:%s:subscription_renewed', $validated['gateway'], $validated['reference']),
            'amount' => $checkout->amount_ngn,
            'currency' => 'NGN',
            'payload' => [
                'checkout_id' => $checkout->id,
                'checkout_reference' => $checkout->reference,
                'transaction_id' => $transactionId,
                'ledger_entry_id' => $ledger->id,
            ],
            'occurred_at' => now(),
        ]);

        $payload = [
            'message' => 'Subscription activated successfully.',
            'subscription' => new SubscriptionResource($subscription->load('plan')),
        ];

        app(PaymentIdempotency::class)->cacheResponse($idempotency['record'], 200, $payload, 'completed', BillingCheckout::class, $checkout->id);

        return response()->json($payload);
    }

    private function resolveIdempotencyKey(Request $request, array $validated = []): ?string
    {
        $header = $request->header('Idempotency-Key') ?? $request->header('X-Idempotency-Key');
        $body = $validated['idempotency_key'] ?? $request->input('idempotency_key');
        $key = is_string($header) && trim($header) !== '' ? $header : (is_string($body) ? $body : null);

        return is_string($key) ? trim($key) : null;
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
}
