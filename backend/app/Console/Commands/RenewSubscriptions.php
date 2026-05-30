<?php

namespace App\Console\Commands;

use App\Models\BillingCheckout;
use App\Models\Subscription;
use App\Support\OperationalEventRecorder;
use App\Support\PaymentLedger;
use App\Support\SubscriptionBillingManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RenewSubscriptions extends Command
{
    protected $signature = 'subscriptions:renew';

    protected $description = 'Charge and renew paid subscriptions that have reached the end of their billing period.';

    public function handle(
        SubscriptionBillingManager $manager,
        PaymentLedger $ledger,
        OperationalEventRecorder $events
    ): int
    {
        if (! config('ops.payments_enabled', true)) {
            $this->warn('Payment renewals are disabled.');

            $events->record([
                'category' => 'monitoring',
                'severity' => 'warning',
                'title' => 'Subscription renewal job skipped.',
                'message' => 'Payments are disabled, so renewals were paused.',
                'source' => 'subscriptions:renew',
                'context' => ['reason' => 'payments_disabled'],
            ]);

            return self::SUCCESS;
        }

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '<=', now())
            ->with(['user', 'plan'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $gateway = strtolower((string) $subscription->gateway);
            $billingInterval = $manager->normalizeInterval($subscription->billing_interval ?? 'monthly');
            $paymentToken = (string) data_get($subscription->metadata, 'payment_token', '');

            if (! in_array($gateway, ['paystack', 'flutterwave'], true)) {
                continue;
            }

            if ($paymentToken === '') {
                $subscription->update([
                    'status' => 'past_due',
                    'expires_at' => $subscription->current_period_ends_at ?? now(),
                ]);

                continue;
            }

            $pendingCheckout = BillingCheckout::query()
                ->where('user_id', $subscription->user_id)
                ->where('plan_id', $subscription->plan_id)
                ->where('billing_interval', $billingInterval)
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('id')
                ->first();

            if ($pendingCheckout) {
                continue;
            }

            $amountNgN = $manager->priceForInterval($subscription->plan, $billingInterval);
            $reference = $manager->buildRenewalReference();
            $checkout = $manager->recordCheckout(
                $subscription->user,
                $subscription->plan,
                $gateway,
                $reference,
                $amountNgN,
                $billingInterval,
                [
                    'kind' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_code' => $subscription->plan?->code,
                    'billing_interval' => $billingInterval,
                ]
            );

            if ($gateway === 'paystack') {
                $response = Http::withToken(config('services.paystack.secret_key'))
                    ->post('https://api.paystack.co/transaction/charge_authorization', [
                        'authorization_code' => $paymentToken,
                        'email' => $subscription->user->email,
                        'amount' => $amountNgN * 100,
                        'reference' => $reference,
                        'currency' => 'NGN',
                        'metadata' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'plan_code' => $subscription->plan?->code,
                            'billing_interval' => $billingInterval,
                        ],
                    ]);

                if ($response->successful() && ($response->json('status') === true || $response->json('data.status') === 'success')) {
                    $checkout->update(['status' => 'paid']);
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'captured',
                        'gateway_event_id' => sprintf('%s:%s:captured', $gateway, $reference),
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:captured', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                            'payment_token' => $paymentToken,
                        ],
                        'occurred_at' => now(),
                    ]);
                    $manager->activateOrRenew(
                        $subscription->user,
                        $subscription->plan,
                        $gateway,
                        $billingInterval,
                        $reference,
                        data_get($response->json('data'), 'id') === null ? null : (string) data_get($response->json('data'), 'id'),
                        [
                            'kind' => 'subscription_renewal',
                            'checkout_id' => $checkout->id,
                            'payment_token' => $paymentToken,
                            'payment_method' => $gateway,
                            'webhook_reference' => $reference,
                        ]
                    );
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'subscription_renewed',
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:subscription_renewed', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                        ],
                        'occurred_at' => now(),
                    ]);
                } else {
                    $checkout->update(['status' => 'failed']);
                    $subscription->update([
                        'status' => 'past_due',
                        'expires_at' => $subscription->current_period_ends_at ?? now(),
                    ]);
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'failed',
                        'gateway_event_id' => sprintf('%s:%s:failed', $gateway, $reference),
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:failed', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                        ],
                        'occurred_at' => now(),
                    ]);
                }

                continue;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.flutterwave.secret_key'),
            ])->post('https://api.flutterwave.com/v3/tokenized-charges', [
                'token' => $paymentToken,
                'currency' => 'NGN',
                'amount' => $amountNgN,
                'tx_ref' => $reference,
                'customer' => [
                    'email' => $subscription->user->email,
                    'name' => $subscription->user->name,
                ],
                'meta' => [
                    'kind' => 'subscription_renewal',
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'plan_code' => $subscription->plan?->code,
                    'billing_interval' => $billingInterval,
                ],
            ]);

                if ($response->successful() && ($response->json('status') === 'success' || $response->json('data.status') === 'successful')) {
                    $checkout->update(['status' => 'paid']);
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'captured',
                        'gateway_event_id' => sprintf('%s:%s:captured', $gateway, $reference),
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:captured', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                            'payment_token' => $paymentToken,
                        ],
                        'occurred_at' => now(),
                    ]);
                    $manager->activateOrRenew(
                        $subscription->user,
                        $subscription->plan,
                        $gateway,
                    $billingInterval,
                    $reference,
                    data_get($response->json('data'), 'id') === null ? null : (string) data_get($response->json('data'), 'id'),
                    [
                            'kind' => 'subscription_renewal',
                            'checkout_id' => $checkout->id,
                            'payment_token' => $paymentToken,
                            'payment_method' => $gateway,
                            'webhook_reference' => $reference,
                        ]
                    );
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'subscription_renewed',
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:subscription_renewed', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                        ],
                        'occurred_at' => now(),
                    ]);
                } else {
                    $checkout->update(['status' => 'failed']);
                    $subscription->update([
                        'status' => 'past_due',
                        'expires_at' => $subscription->current_period_ends_at ?? now(),
                    ]);
                    $ledger->append([
                        'user_id' => $subscription->user_id,
                        'billing_checkout_id' => $checkout->id,
                        'subscription_id' => $subscription->id,
                        'gateway' => $gateway,
                        'event_type' => 'failed',
                        'gateway_event_id' => sprintf('%s:%s:failed', $gateway, $reference),
                        'gateway_reference' => $reference,
                        'dedupe_key' => sprintf('%s:%s:failed', $gateway, $reference),
                        'amount' => $amountNgN,
                        'currency' => 'NGN',
                        'payload' => [
                            'kind' => 'subscription_renewal',
                            'subscription_id' => $subscription->id,
                            'checkout_id' => $checkout->id,
                        ],
                        'occurred_at' => now(),
                    ]);
                }
        }

        $this->info("Processed {$subscriptions->count()} subscription renewal(s).");

        return self::SUCCESS;
    }
}
