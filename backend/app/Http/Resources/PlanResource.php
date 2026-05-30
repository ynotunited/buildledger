<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'price_ngn' => $this->price_ngn,
            'price_annually_ngn' => $this->price_annually_ngn,
            'billing_interval' => $this->billing_interval,
            'features' => $this->features ?? [],
            'company_limit' => $this->company_limit,
            'is_active' => $this->is_active,
        ];
    }
}
