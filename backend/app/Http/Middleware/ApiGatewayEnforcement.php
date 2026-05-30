<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiGatewayEnforcement
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.api_gateway_enforced') || app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $allowedHosts = config('security.api_gateway_allowed_hosts', []);
        if ($allowedHosts !== [] && ! in_array($request->getHost(), $allowedHosts, true)) {
            return response()->json(['message' => 'Request host is not allowed.'], 403);
        }

        $sharedSecret = (string) config('security.api_gateway_shared_secret');
        if ($sharedSecret !== '') {
            $headerSecret = (string) $request->headers->get('X-Gateway-Token', '');
            if (! hash_equals($sharedSecret, $headerSecret)) {
                return response()->json(['message' => 'API gateway validation failed.'], 403);
            }
        }

        if (config('security.api_gateway_require_request_id') && ! $request->headers->has('X-Request-Id')) {
            return response()->json(['message' => 'Missing request identifier.'], 400);
        }

        if (($request->headers->get('X-Forwarded-Proto') ?? 'https') !== 'https') {
            return response()->json(['message' => 'Gateway must forward HTTPS requests only.'], 400);
        }

        return $next($request);
    }
}
