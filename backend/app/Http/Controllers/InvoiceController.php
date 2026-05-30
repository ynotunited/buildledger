<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Notifications\InvoicePaymentLinkSent;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $request->user()
            ->invoices()
            ->with(['client', 'contract', 'company'])
            ->latest()
            ->paginate(20);

        return InvoiceResource::collection($invoices);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['contract_id'])) {
            $contract = $request->user()->contracts()->findOrFail($validated['contract_id']);

            if ((int) $contract->client_id !== (int) $validated['client_id']) {
                throw ValidationException::withMessages([
                    'contract_id' => 'The selected contract does not belong to the selected client.',
                ]);
            }
        }

        $invoice = DB::transaction(function () use ($request, $validated) {
            $company = $request->user()->company;
            $subtotal = collect($validated['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $discount = $validated['discount'] ?? 0;
            $tax      = 0;
            $total    = $subtotal - $discount + $tax;

            // Race-condition-safe invoice number using DB lock
            $lastInvoice   = $request->user()->invoices()->lockForUpdate()->latest('id')->first();
            $nextId        = $lastInvoice ? $lastInvoice->id + 1 : 1;
            $invoiceNumber = 'INV-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $invoice = $request->user()->invoices()->create([
                'company_id'     => $company?->id,
                'client_id'      => $validated['client_id'],
                'contract_id'    => $validated['contract_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'status'         => 'Draft',
                'issue_date'     => $validated['issue_date'],
                'due_date'       => $validated['due_date'],
                'notes'          => $validated['notes'] ?? null,
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'discount'       => $discount,
                'total'          => $total,
            ]);

            foreach ($validated['items'] as $item) {
                $invoice->items()->create([
                    'name'        => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total'       => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $invoice->load('items', 'client', 'contract', 'company');
        });

        return new InvoiceResource($invoice);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByUser($request, $invoice);

        return new InvoiceResource($invoice->load(['client', 'contract', 'items', 'company']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByUser($request, $invoice);

        $validated = $request->validate([
            'status'     => 'sometimes|required|string|in:Draft,Sent,Paid,Overdue',
            'issue_date' => 'sometimes|required|date',
            'due_date'   => 'sometimes|required|date',
            'notes'      => 'nullable|string|max:5000',
        ]);

        $invoice->update([
            'status' => $validated['status'] ?? $invoice->status,
            'issue_date' => $validated['issue_date'] ?? $invoice->issue_date,
            'due_date' => $validated['due_date'] ?? $invoice->due_date,
            'notes' => array_key_exists('notes', $validated) ? InputSanitizer::multilineText($validated['notes']) : $invoice->notes,
        ]);

        if (($validated['status'] ?? null) === 'Sent' && ! $invoice->hasActivePaymentLink()) {
            $this->issuePaymentLink($invoice);
        } elseif (($validated['status'] ?? null) !== null && ($validated['status'] ?? null) !== 'Sent') {
            $this->revokePaymentLink($invoice);
        }

        return new InvoiceResource($invoice->load(['client', 'contract', 'items', 'company']));
    }

    public function sendPaymentLink(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByUser($request, $invoice);

        if ($invoice->status === 'Paid') {
            return response()->json([
                'message' => 'Paid invoices do not need a payment link.',
            ], 409);
        }

        $invoice->loadMissing(['client', 'company']);

        if (! $invoice->client?->email) {
            throw ValidationException::withMessages([
                'client_id' => 'The selected client must have an email address before sending a payment link.',
            ]);
        }

        $this->issuePaymentLink($invoice);

        $invoice->load(['client', 'contract', 'items', 'company']);

        $paymentUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/') . "/pay/{$invoice->public_payment_token}";

        Notification::route('mail', $invoice->client->email)
            ->notify(new InvoicePaymentLinkSent($invoice, $paymentUrl));

        return new InvoiceResource($invoice);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByUser($request, $invoice);

        $invoice->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function generatePdf(Request $request, Invoice $invoice)
    {
        $this->ensureOwnedByUser($request, $invoice);

        $invoice->load(['client', 'items', 'user', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.invoice', compact('invoice'));

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    private function issuePaymentLink(Invoice $invoice): void
    {
        $invoice->forceFill([
            'status' => 'Sent',
            'sent_at' => $invoice->sent_at ?? now(),
            'public_payment_token' => (string) Str::uuid(),
            'public_payment_token_expires_at' => Carbon::now()->addHours(
                (int) config('security.invoice_payment_link_ttl_hours', 168)
            ),
        ])->save();
    }

    private function revokePaymentLink(Invoice $invoice): void
    {
        $invoice->forceFill([
            'public_payment_token' => null,
            'public_payment_token_expires_at' => null,
            'sent_at' => $invoice->status === 'Paid' ? $invoice->sent_at : null,
        ])->save();
    }
}
