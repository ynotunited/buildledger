<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Notifications\InvoiceOverdue;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature   = 'invoices:mark-overdue';
    protected $description = 'Mark sent invoices past their due date as Overdue and notify owners.';

    public function handle(): int
    {
        $overdue = Invoice::whereIn('status', ['Draft', 'Sent'])
            ->whereDate('due_date', '<', now())
            ->with('user')
            ->get();

        foreach ($overdue as $invoice) {
            $invoice->update(['status' => 'Overdue']);
            $invoice->user->notify(new InvoiceOverdue($invoice));
        }

        $this->info("Marked {$overdue->count()} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
