<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $company = $this->company ?? $this->user?->company;
        $hasActivePaymentLink = $this->status === 'Sent'
            && $this->public_payment_token
            && $this->public_payment_token_expires_at
            && $this->public_payment_token_expires_at->isFuture();

        return [
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'discount' => $this->discount,
            'total' => $this->total,
            'notes' => $this->notes,
            'client' => [
                'name' => $this->client->name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
            ],
            'company' => $company ? [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'website' => $company->website,
                'tax_id' => $company->tax_id,
                'logo_url' => $company->logo_url,
            ] : null,
            'items' => $this->items->map(fn ($item) => [
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]),
            'public_payment_link_expires_at' => $hasActivePaymentLink ? $this->public_payment_token_expires_at : null,
            'public_payment_path' => $hasActivePaymentLink ? "/pay/{$this->public_payment_token}" : null,
        ];
    }
}
