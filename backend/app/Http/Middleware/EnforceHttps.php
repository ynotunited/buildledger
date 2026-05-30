<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (
            ! config('security.enforce_https')
            || app()->environment(['local', 'testing'])
            || $request->isSecure()
        ) {
            return $next($request);
        }

        $secureUrl = 'https://' . $request->getHttpHost() . $request->getRequestUri();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'HTTPS is required for this application.',
                'redirect_to' => $secureUrl,
            ], 426);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }
}
