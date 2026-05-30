<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InvoicePaymentLinkSent extends Notification
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public string $paymentUrl,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->invoice->company?->name ?? config('app.name');
        $recipientName = $this->invoice->client?->name ?? 'there';
        $currency = 'NGN';
        $dueDate = $this->invoice->due_date ? Carbon::parse($this->invoice->due_date)->format('M j, Y') : null;

        return (new MailMessage)
            ->subject("Invoice ready for payment: {$this->invoice->invoice_number}")
            ->greeting("Hi {$recipientName},")
            ->line("{$companyName} has shared invoice **{$this->invoice->invoice_number}** with you.")
            ->line('Amount due: ' . number_format((float) $this->invoice->total, 2) . " {$currency}")
            ->lineIf($dueDate, 'Due date: ' . $dueDate)
            ->action('Pay Invoice', $this->paymentUrl)
            ->line('Use the button above to complete payment securely.')
            ->salutation("Thanks,\n{$companyName}");
    }
}
