<?php

namespace App\Support;

use App\Models\User;
use App\Models\WaitlistSignup;
use Illuminate\Validation\ValidationException;

class WaitlistAccessManager
{
    public function inviteOnly(): bool
    {
        return app(InviteModeManager::class)->isInviteOnly();
    }

    public function canAccess(string $email): bool
    {
        if (! $this->inviteOnly()) {
            return true;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        if (User::query()->where('email', $normalizedEmail)->exists()) {
            return true;
        }

        return WaitlistSignup::query()
            ->where('email', $normalizedEmail)
            ->whereIn('status', [
                WaitlistSignup::STATUS_APPROVED,
                WaitlistSignup::STATUS_ACTIVATED,
            ])
            ->exists();
    }

    public function ensureCanAccess(string $email): void
    {
        if ($this->canAccess($email)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => ['This app is invite-only. Join the waitlist or use an approved invitation email.'],
        ]);
    }

    public function markActivated(string $email): void
    {
        if (! $this->inviteOnly()) {
            return;
        }

        $normalizedEmail = mb_strtolower(trim($email));

        WaitlistSignup::query()
            ->where('email', $normalizedEmail)
            ->whereIn('status', [
                WaitlistSignup::STATUS_PENDING,
                WaitlistSignup::STATUS_APPROVED,
            ])
            ->update([
                'status' => WaitlistSignup::STATUS_ACTIVATED,
                'activated_at' => now(),
            ]);
    }
}
