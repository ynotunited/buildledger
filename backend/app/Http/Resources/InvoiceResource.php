<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasActivePaymentLink = $this->hasActivePaymentLink();

        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'status'         => $this->status,
            'sent_at'        => $this->sent_at,
            'issue_date'     => $this->issue_date,
            'due_date'       => $this->due_date,
            'subtotal'       => $this->subtotal,
            'tax'            => $this->tax,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'notes'          => $this->notes,
            'client'         => $this->whenLoaded('client', fn () => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
            ]),
            'contract'       => $this->whenLoaded('contract', fn () => $this->contract ? [
                'id'    => $this->contract->id,
                'title' => $this->contract->title,
            ] : null),
            'company'        => $this->whenLoaded('company', fn () => $this->company ? [
                'id'       => $this->company->id,
                'name'     => $this->company->name,
                'email'    => $this->company->email,
                'phone'    => $this->company->phone,
                'address'  => $this->company->address,
                'website'  => $this->company->website,
                'tax_id'   => $this->company->tax_id,
                'logo_url' => $this->company->logo_url,
            ] : null),
            'public_payment_link_expires_at' => $hasActivePaymentLink ? $this->public_payment_token_expires_at : null,
            'public_payment_path' => $hasActivePaymentLink ? "/pay/{$this->public_payment_token}" : null,
            'items'          => $this->whenLoaded('items'),
            'created_at'     => $this->created_at,
        ];
    }
}
