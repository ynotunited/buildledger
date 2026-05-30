<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'gateway_reference' => $this->gateway_reference,
            'current_period_starts_at' => $this->current_period_starts_at,
            'current_period_ends_at' => $this->current_period_ends_at,
            'cancelled_at' => $this->cancelled_at,
            'expires_at' => $this->expires_at,
            'billing_interval' => $this->billing_interval,
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? (new PlanResource($this->plan))->resolve() : null),
        ];
    }
}
