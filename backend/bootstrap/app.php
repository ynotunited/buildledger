<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Support\ApplicationErrorRecorder;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('SECURITY_TRUSTED_PROXIES', '*'));
        $middleware->statefulApi();

        // Apply rate limiting to all API routes
        $middleware->throttleApi();

        // Apply stricter rate limiting to auth routes specifically
        $middleware->group('auth-throttle', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':auth',
        ]);

        // Security headers on all responses
        $middleware->prepend(\App\Http\Middleware\EnforceHttps::class);
        $middleware->prepend(\App\Http\Middleware\ApiGatewayEnforcement::class);
        $middleware->alias([
            'db.context' => \App\Http\Middleware\DatabaseSecurityContext::class,
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'subscription.feature' => \App\Http\Middleware\EnsureSubscriptionFeature::class,
        ]);
        $middleware->append(\App\Http\Middleware\RequestSecurityMonitor::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
        $resolveStatusCode = static function (\Throwable $exception): int {
            if ($exception instanceof AuthenticationException) {
                return 401;
            }

            if ($exception instanceof TokenMismatchException) {
                return 419;
            }

            if ($exception instanceof ValidationException) {
                return 422;
            }

            if ($exception instanceof HttpExceptionInterface) {
                return $exception->getStatusCode();
            }

            if (method_exists($exception, 'getStatusCode')) {
                return (int) $exception->getStatusCode();
            }

            return 500;
        };

        // Return JSON for all API exceptions
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $exception, Request $request) use ($resolveStatusCode) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $resolveStatusCode($exception);

                if ($status >= 500) {
                    Log::channel('api')->error('Unhandled API exception.', [
                        'message' => $exception->getMessage(),
                        'exception' => $exception::class,
                        'status' => $status,
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'ip' => $request->ip(),
                        'user_id' => $request->user()?->id,
                    ]);
                }

                ApplicationErrorRecorder::fromException($exception, $request, $status);
            }

            return null;
        });
    })->create();
