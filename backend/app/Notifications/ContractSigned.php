<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractSigned extends Notification
{
    use Queueable;

    public function __construct(public Contract $contract) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Contract signed: {$this->contract->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your contract **{$this->contract->title}** has been signed by the client.")
            ->line("Signed by: {$this->contract->client_signature_name}")
            ->action('View Contract', config('app.frontend_url') . "/contracts/{$this->contract->id}")
            ->line('You can now convert it to an invoice.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'contract_signed',
            'title'   => 'Contract Signed',
            'message' => "Contract \"{$this->contract->title}\" has been signed by {$this->contract->client_signature_name}.",
            'link'    => "/contracts/{$this->contract->id}",
        ];
    }
}
