<?php

namespace App\Http\Middleware;

use App\Support\RowLevelSecurity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DatabaseSecurityContext
{
    public function handle(Request $request, Closure $next): Response
    {
        RowLevelSecurity::applyRequestContext($request);

        return $next($request);
    }
}
