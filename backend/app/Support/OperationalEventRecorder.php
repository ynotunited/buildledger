<?php

namespace App\Support;

use App\Models\OperationalEvent;
use App\Models\User;
use App\Notifications\OperationalEventRaised;
use Illuminate\Support\Facades\Notification;

class OperationalEventRecorder
{
    public function record(array $attributes): OperationalEvent
    {
        $event = OperationalEvent::create([
            'user_id' => $attributes['user_id'] ?? null,
            'category' => $attributes['category'],
            'severity' => $attributes['severity'] ?? 'info',
            'title' => $attributes['title'],
            'message' => $attributes['message'] ?? null,
            'source' => $attributes['source'] ?? null,
            'reference_type' => $attributes['reference_type'] ?? null,
            'reference_id' => $attributes['reference_id'] ?? null,
            'context' => $attributes['context'] ?? null,
            'occurred_at' => $attributes['occurred_at'] ?? now(),
            'resolved_at' => $attributes['resolved_at'] ?? null,
        ]);

        $this->notifyAdmins($event);

        return $event;
    }

    private function notifyAdmins(OperationalEvent $event): void
    {
        if (! config('ops.alerts_enabled', true)) {
            return;
        }

        if (! in_array($event->severity, ['warning', 'critical'], true)) {
            return;
        }

        $admins = User::query()->where('role', User::ROLE_ADMIN)->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new OperationalEventRaised($event));
    }
}
