<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaid extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received for {$this->invoice->invoice_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Great news! Payment has been received for invoice **{$this->invoice->invoice_number}**.")
            ->line('Amount: ' . number_format($this->invoice->total, 2) . ' ' . 'NGN')
            ->action('View Invoice', config('app.frontend_url') . "/invoices/{$this->invoice->id}")
            ->line('Thank you for using BuildLedger.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'invoice_paid',
            'title'   => 'Payment Received',
            'message' => "Invoice {$this->invoice->invoice_number} has been paid.",
            'link'    => "/invoices/{$this->invoice->id}",
        ];
    }
}
