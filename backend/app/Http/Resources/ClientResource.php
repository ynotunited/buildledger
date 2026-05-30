<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'company'    => $this->company,
            'status'     => $this->status,
            'address'    => $this->address,
            'owner_name' => $request->user()?->isAdmin() ? $this->user?->name : null,
            'owner_email' => $request->user()?->isAdmin() ? $this->user?->email : null,
            'created_at' => $this->created_at,
        ];
    }
}
