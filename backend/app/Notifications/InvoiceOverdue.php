<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdue extends Notification
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
            ->subject("Invoice overdue: {$this->invoice->invoice_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Invoice **{$this->invoice->invoice_number}** is now overdue.")
            ->line('Due date: ' . $this->invoice->due_date->format('M d, Y'))
            ->line('Amount: ₦' . number_format($this->invoice->total, 2))
            ->action('View Invoice', config('app.frontend_url') . "/invoices/{$this->invoice->id}")
            ->line('Please follow up with your client.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'invoice_overdue',
            'title'   => 'Invoice Overdue',
            'message' => "Invoice {$this->invoice->invoice_number} is overdue.",
            'link'    => "/invoices/{$this->invoice->id}",
        ];
    }
}
