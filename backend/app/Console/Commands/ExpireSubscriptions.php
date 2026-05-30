<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions that have reached the end of their billing period.';

    public function handle(): int
    {
        $expired = Subscription::query()
            ->whereIn('status', ['active', 'past_due'])
            ->where(function ($query) {
                $query->where(function ($nested) {
                    $nested->whereNotNull('current_period_ends_at')
                        ->where('current_period_ends_at', '<', now());
                })->orWhere(function ($nested) {
                    $nested->whereNotNull('expires_at')
                        ->where('expires_at', '<', now());
                });
            })
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update([
                'status' => 'expired',
                'expires_at' => $subscription->expires_at ?? now(),
            ]);
        }

        $this->info("Marked {$expired->count()} subscription(s) as expired.");

        return self::SUCCESS;
    }
}
