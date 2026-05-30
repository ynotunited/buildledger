<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->hasFeature($feature)) {
            return $next($request);
        }

        $plan = $user->currentPlan();

        return response()->json([
            'message' => ucfirst(str_replace('_', ' ', $feature)) . ' is available on a higher plan.',
            'requires_upgrade' => true,
            'required_feature' => $feature,
            'current_plan' => $plan?->code,
        ], 402);
    }
}
