<?php

namespace App\Notifications;

use App\Models\WaitlistSignup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaitlistInvitationApproved extends Notification
{
    use Queueable;

    public function __construct(
        private readonly WaitlistSignup $signup,
        private readonly string $approvedByName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        $registerUrl = "{$frontendUrl}/register?email=" . urlencode($this->signup->email);
        $greetingName = $this->signup->name ?: 'there';

        return (new MailMessage)
            ->subject('Your BuildLedger invitation is ready')
            ->greeting("Hi {$greetingName},")
            ->line('You have been approved to create your BuildLedger account.')
            ->line("This invitation was approved by {$this->approvedByName}.")
            ->action('Create your account', $registerUrl)
            ->line('Use the same email address you used on the waitlist so we can match your invitation.');
    }
}
