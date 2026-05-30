<?php

namespace App\Notifications;

use App\Models\OperationalEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationalEventRaised extends Notification
{
    use Queueable;

    public function __construct(public OperationalEvent $event)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severity = strtoupper($this->event->severity);

        return (new MailMessage)
            ->subject("[{$severity}] {$this->event->title}")
            ->line($this->event->message ?? 'An operational event was recorded.')
            ->line('Category: ' . $this->event->category)
            ->line('Source: ' . ($this->event->source ?? 'unknown'))
            ->line('Occurred at: ' . optional($this->event->occurred_at)->toDateTimeString());
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'operational_event',
            'category' => $this->event->category,
            'severity' => $this->event->severity,
            'title' => $this->event->title,
            'message' => $this->event->message,
            'source' => $this->event->source,
            'occurred_at' => optional($this->event->occurred_at)->toIso8601String(),
        ];
    }
}
