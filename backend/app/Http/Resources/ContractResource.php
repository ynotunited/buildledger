<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasActiveSigningLink = $this->status === 'Sent'
            && $this->signing_token
            && $this->signing_token_expires_at
            && $this->signing_token_expires_at->isFuture();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'body_content' => $this->body_content,
            'status' => $this->status,
            'sent_at' => $this->sent_at,
            'client_signed_at' => $this->client_signed_at,
            'signing_token' => $hasActiveSigningLink ? $this->signing_token : null,
            'signing_link_expires_at' => $hasActiveSigningLink ? $this->signing_token_expires_at : null,
            'public_signing_path' => $hasActiveSigningLink ? "/contracts/sign/{$this->signing_token}" : null,
            'client' => $this->whenLoaded('client', fn () => $this->client ? [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ] : null),
            'proposal' => $this->whenLoaded('proposal', fn () => $this->proposal ? [
                'id' => $this->proposal->id,
                'title' => $this->proposal->title,
                'status' => $this->proposal->status,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => $this->company ? [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'email' => $this->company->email,
                'phone' => $this->company->phone,
                'address' => $this->company->address,
                'website' => $this->company->website,
                'tax_id' => $this->company->tax_id,
                'logo_url' => $this->company->logo_url,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
