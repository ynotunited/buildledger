<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\OperationalEventRecorder;

class InviteModeManager
{
    public const SETTING_KEY = 'invite_only';

    public function isInviteOnly(): bool
    {
        $override = AppSetting::query()
            ->where('key', self::SETTING_KEY)
            ->value('value');

        if ($override !== null) {
            return filter_var($override, FILTER_VALIDATE_BOOL);
        }

        return (bool) config('security.invite_only', false);
    }

    public function source(): string
    {
        return AppSetting::query()->where('key', self::SETTING_KEY)->exists() ? 'database' : 'env';
    }

    public function updatedAt(): ?string
    {
        return AppSetting::query()
            ->where('key', self::SETTING_KEY)
            ->value('updated_at');
    }

    public function setInviteOnly(bool $enabled, ?User $actor = null): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => $enabled ? '1' : '0']
        );

        app(OperationalEventRecorder::class)->record([
            'category' => 'launch',
            'severity' => 'info',
            'title' => $enabled ? 'Invite-only enabled' : 'Invite-only disabled',
            'message' => $enabled
                ? 'Registration now requires an approved invitation.'
                : 'Open registration has been restored.',
            'source' => 'admin-console',
            'user_id' => $actor?->id,
            'context' => [
                'invite_only' => $enabled,
                'setting_source' => 'database',
            ],
        ]);
    }
}
