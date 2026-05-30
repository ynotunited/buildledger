<?php

namespace App\Support;

use App\Models\SecurityIncident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SecurityIncidentRecorder
{
    public static function record(
        Request $request,
        string $type,
        string $severity = 'warning',
        array $context = [],
        ?string $identityKey = null
    ): void {
        $payload = [
            'user_id' => $request->user()?->id,
            'type' => $type,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'identity_key' => $identityKey,
            'context' => $context,
            'occurred_at' => now(),
        ];

        Log::channel('security')->log($severity, 'Security incident recorded.', $payload);

        try {
            SecurityIncident::create($payload);
        } catch (\Throwable $exception) {
            Log::channel('security')->warning('Failed to persist security incident.', [
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
