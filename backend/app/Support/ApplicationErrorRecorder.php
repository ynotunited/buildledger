<?php

namespace App\Support;

use App\Models\ApplicationError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApplicationErrorRecorder
{
    public static function record(array $payload): void
    {
        try {
            RowLevelSecurity::runWithContext([
                'app.user_id' => '',
                'app.user_role' => '',
                'app.access_mode' => 'public',
                'app.public_access_token' => '',
            ], fn () => ApplicationError::create($payload));
        } catch (\Throwable $exception) {
            Log::channel('api')->warning('Failed to persist application error.', [
                'message' => $payload['message'] ?? 'unknown',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public static function fromException(\Throwable $exception, Request $request, int $status): void
    {
        if ($status < 500) {
            return;
        }

        self::record([
            'user_id' => $request->user()?->id,
            'source' => 'backend',
            'level' => $status >= 500 ? 'error' : 'warning',
            'message' => $exception->getMessage(),
            'exception_class' => $exception::class,
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->headers->get('X-Request-Id'),
            'context' => [
                'status' => $status,
                'method' => $request->method(),
            ],
            'occurred_at' => now(),
        ]);
    }
}
