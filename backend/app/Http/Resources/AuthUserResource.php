<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $session = $request->hasSession() ? $request->session() : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'is_google_account' => (bool) $this->google_id,
            'trial_ends_at' => $this->trial_ends_at,
            'is_impersonating' => $session?->has('support_impersonator_user_id') ?? false,
            'impersonator_name' => $session?->get('support_impersonator_name'),
            'impersonator_email' => $session?->get('support_impersonator_email'),
            'impersonated_at' => $session?->get('support_impersonated_at'),
        ];
    }
}
