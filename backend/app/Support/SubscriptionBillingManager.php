<?php

namespace App\Support;

use App\Models\BillingCheckout;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SubscriptionBillingManager
{
    public function normalizeInterval(?string $interval): string
    {
        return in_array($interval, ['monthly', 'annual'], true) ? $interval : 'monthly';
    }

    public function priceForInterval(Plan $plan, string $interval): int
    {
        $interval = $this->normalizeInterval($interval);

        if ($interval === 'annual') {
            return (int) ($plan->price_annually_ngn ?? ($plan->price_ngn * 12));
        }

        return (int) $plan->price_ngn;
    }

    public function periodEndForInterval(string $interval, ?Carbon $start = null): Carbon
    {
        $interval = $this->normalizeInterval($interval);
        $start ??= now();

        return $interval === 'annual'
            ? $start->copy()->addYear()
            : $start->copy()->addMonth();
    }

    public function trialEndDate(?Carbon $start = null): Carbon
    {
        $start ??= now();

        return $start->copy()->addDays((int) config('billing.trial_days', 30));
    }

    public function ensureTrialWindow(User $user): User
    {
        if ($user->trial_ends_at === null) {
            $user->forceFill([
                'trial_ends_at' => $this->trialEndDate(),
            ])->save();
        }

        return $user->fresh();
    }

    public function hasActiveTrial(User $user): bool
    {
        return $user->trial_ends_at !== null && $user->trial_ends_at->isFuture();
    }

    public function calculateProrationCredit(User $user, Plan $plan, string $billingInterval): int
    {
        $activeSubscription = $user->activeSubscription()->with('plan')->first();

        if (! $activeSubscription || ! $activeSubscription->plan || ! $activeSubscription->current_period_ends_at) {
            return 0;
        }

        $billingInterval = $this->normalizeInterval($billingInterval);
        $currentInterval = $this->normalizeInterval($activeSubscription->billing_interval ?? 'monthly');

        if ($activeSubscription->plan_id === $plan->id && $currentInterval === $billingInterval) {
            return 0;
        }

        $periodEndsAt = $activeSubscription->current_period_ends_at;

        if ($periodEndsAt->isPast()) {
            return 0;
        }

        $periodStartsAt = $activeSubscription->current_period_starts_at ?? now();
        $cycleSeconds = max(1, $periodStartsAt->diffInSeconds($periodEndsAt));
        $remainingSeconds = max(0, now()->diffInSeconds($periodEndsAt));
        $currentPrice = $this->priceForInterval($activeSubscription->plan, $currentInterval);

        return (int) round($currentPrice * ($remainingSeconds / $cycleSeconds));
    }

    public function recordCheckout(
        User $user,
        Plan $plan,
        string $gateway,
        string $reference,
        int $amountNgN,
        string $billingInterval,
        array $metadata = [],
        string $status = 'pending'
    ): BillingCheckout {
        return BillingCheckout::updateOrCreate(
            ['reference' => $reference],
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'gateway' => $gateway,
                'billing_interval' => $this->normalizeInterval($billingInterval),
                'amount_ngn' => $amountNgN,
                'status' => $status,
                'expires_at' => now()->addHour(),
                'metadata' => $metadata,
            ]
        );
    }

    public function activateOrRenew(
        User $user,
        Plan $plan,
        string $gateway,
        string $billingInterval,
        ?string $reference,
        ?string $transactionId,
        array $metadata = []
    ): Subscription {
        $billingInterval = $this->normalizeInterval($billingInterval);
        $activeSubscription = $user->subscriptions()->where('status', 'active')->latest('id')->first();
        $cycleStart = now();
        $cycleEnd = $this->periodEndForInterval($billingInterval, $cycleStart);

        if ($activeSubscription && $reference !== null) {
            $appliedReference = data_get($activeSubscription->metadata, 'webhook_reference')
                ?? data_get($activeSubscription->metadata, 'checkout_reference')
                ?? $activeSubscription->gateway_reference;

            if ($appliedReference === $reference) {
                return $activeSubscription->fresh()->load('plan');
            }
        }

        $mergedMetadata = array_merge($activeSubscription?->metadata ?? [], $metadata, [
            'gateway' => $gateway,
            'billing_interval' => $billingInterval,
            'last_billed_at' => now()->toIso8601String(),
        ]);

        if ($activeSubscription && $activeSubscription->plan_id === $plan->id && $this->normalizeInterval($activeSubscription->billing_interval ?? 'monthly') === $billingInterval) {
            $cycleStart = $activeSubscription->current_period_ends_at && $activeSubscription->current_period_ends_at->isFuture()
                ? $activeSubscription->current_period_ends_at->copy()
                : now();
            $cycleEnd = $this->periodEndForInterval($billingInterval, $cycleStart);

            $renewalCount = (int) data_get($activeSubscription->metadata, 'renewal_count', 0) + 1;

            $activeSubscription->update([
                'gateway' => $gateway,
                'gateway_reference' => $reference ?? $activeSubscription->gateway_reference,
                'gateway_transaction_id' => $transactionId ?? $activeSubscription->gateway_transaction_id,
                'billing_interval' => $billingInterval,
                'current_period_starts_at' => $cycleStart,
                'current_period_ends_at' => $cycleEnd,
                'expires_at' => $cycleEnd,
                'metadata' => array_merge($mergedMetadata, [
                    'renewal_count' => $renewalCount,
                ]),
            ]);

            return $activeSubscription->fresh()->load('plan');
        }

        if ($activeSubscription) {
            $activeSubscription->update([
                'status' => 'expired',
                'expires_at' => now(),
            ]);
        }

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'gateway' => $gateway,
            'gateway_reference' => $reference,
            'gateway_transaction_id' => $transactionId,
            'billing_interval' => $billingInterval,
            'current_period_starts_at' => $cycleStart,
            'current_period_ends_at' => $cycleEnd,
            'expires_at' => $cycleEnd,
            'metadata' => array_merge($mergedMetadata, [
                'renewal_count' => 0,
            ]),
        ])->load('plan');
    }

    public function startTrial(User $user): void
    {
        if ($user->trial_ends_at !== null) {
            return;
        }

        if ($user->subscriptions()->where('status', 'active')->exists()) {
            return;
        }

        $user->forceFill([
            'trial_ends_at' => $this->trialEndDate(),
        ])->save();
    }

    public function buildRenewalReference(string $prefix = 'REN'): string
    {
        return sprintf('%s-%s', strtoupper($prefix), strtoupper(Str::random(14)));
    }
}
