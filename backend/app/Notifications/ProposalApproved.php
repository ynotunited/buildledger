<?php

namespace App\Notifications;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposalApproved extends Notification
{
    use Queueable;

    public function __construct(public Proposal $proposal) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Proposal approved: {$this->proposal->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your proposal **{$this->proposal->title}** has been approved by the client.")
            ->action('View Proposal', config('app.frontend_url') . "/proposals/{$this->proposal->id}")
            ->line('You can now convert it to a contract.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'proposal_approved',
            'title'   => 'Proposal Approved',
            'message' => "Proposal \"{$this->proposal->title}\" has been approved.",
            'link'    => "/proposals/{$this->proposal->id}",
        ];
    }
}
