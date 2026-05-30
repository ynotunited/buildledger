<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'status' => $this->status,
            'body_content' => $this->body_content,
            'client' => [
                'name' => $this->client?->name,
            ],
            'company' => $this->company ? [
                'name' => $this->company->name,
                'email' => $this->company->email,
                'website' => $this->company->website,
                'logo_url' => $this->company->logo_url,
            ] : null,
            'signing_link_expires_at' => $this->signing_token_expires_at,
        ];
    }
}
