<?php

namespace App\Providers;

use App\Models\User;
use App\Support\SecurityIncidentRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureSecureUrls();
        $this->checkDistributedRateLimitReadiness();
        $this->configureRateLimiting();
        $this->configureAuthNotifications();
    }

    private function configureSecureUrls(): void
    {
        if (config('security.enforce_https') && ! app()->environment(['local', 'testing'])) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = $this->normalizeIdentity($request->input('email'));

            return [
                Limit::perMinute(5)
                    ->by("login:identity:{$email}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many login attempts for this account. Please wait a minute and try again.', "login:identity:{$email}")),
                Limit::perMinute(20)
                    ->by("login:ip:".$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many login attempts from this network. Please wait a minute and try again.', "login:ip:".$request->ip())),
                Limit::perMinute(10)
                    ->by("login:fingerprint:".$this->requestFingerprint($request))
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many login attempts from this device. Please wait a minute and try again.', "login:fingerprint:".$this->requestFingerprint($request))),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            $email = $this->normalizeIdentity($request->input('email'));

            return [
                Limit::perMinutes(10, 3)
                    ->by("register:ip:".$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many account creation attempts from this network. Please wait before trying again.', "register:ip:".$request->ip())),
                Limit::perMinutes(60, 5)
                    ->by("register:identity:{$email}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many account creation attempts for this email address. Please try again later.', "register:identity:{$email}")),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $email = $this->normalizeIdentity($request->input('email'));

            return [
                Limit::perMinutes(15, 3)
                    ->by("password-reset:identity:{$email}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many password reset attempts for this account. Please try again later.', "password-reset:identity:{$email}")),
                Limit::perMinutes(15, 8)
                    ->by("password-reset:ip:".$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many password reset attempts from this network. Please try again later.', "password-reset:ip:".$request->ip())),
            ];
        });

        RateLimiter::for('verification', function (Request $request) {
            $key = $request->user()?->id
                ? 'user:'.$request->user()->id
                : $this->normalizeIdentity($request->input('email'));

            return [
                Limit::perMinutes(15, 6)
                    ->by("verification:identity:{$key}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many verification attempts for this account. Please try again later.', "verification:identity:{$key}")),
                Limit::perMinutes(15, 10)
                    ->by("verification:ip:".$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many verification attempts from this network. Please try again later.', "verification:ip:".$request->ip())),
            ];
        });

        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(90)
                    ->by('user:'.$request->user()->id)
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many API requests. Please slow down.'))
                : Limit::perMinute(30)
                    ->by('ip:'.$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many API requests. Please slow down.'));
        });

        RateLimiter::for('payments', function (Request $request) {
            $identity = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

            return [
                Limit::perMinute(5)
                    ->by("payments:identity:{$identity}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many payment attempts for this account. Please wait and try again.', "payments:identity:{$identity}")),
                Limit::perMinute(12)
                    ->by("payments:ip:".$request->ip())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many payment attempts from this network. Please wait and try again.', "payments:ip:".$request->ip())),
            ];
        });

        RateLimiter::for('paid-api', function (Request $request) {
            $identity = $request->user()?->id
                ? 'user:'.$request->user()->id
                : 'ip:'.$request->ip();
            $routeKey = $request->path();
            $minuteLimit = max(1, (int) config('security.paid_api_requests_per_minute', 8));
            $hourLimit = max(1, (int) config('security.paid_api_requests_per_hour', 40));

            return [
                Limit::perMinute($minuteLimit)
                    ->by("paid-api:{$identity}:{$routeKey}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many paid API requests. Please wait and try again.', "paid-api:{$identity}:{$routeKey}")),
                Limit::perHour($hourLimit)
                    ->by("paid-api-hour:{$identity}:{$routeKey}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many paid API requests. Please wait before trying again.', "paid-api-hour:{$identity}:{$routeKey}")),
            ];
        });

        RateLimiter::for('documents', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many document requests. Please wait before trying again.'));
        });

        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many upload attempts. Please wait before trying again.'));
        });

        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many notification requests. Please slow down.'));
        });

        RateLimiter::for('public-documents', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip().'|'.$request->path())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many public document requests. Please wait and try again.'));
        });

        RateLimiter::for('public-signing', function (Request $request) {
            return Limit::perMinutes(10, 10)
                ->by($request->ip().'|'.$request->path())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many signing attempts. Please wait before trying again.'));
        });

        RateLimiter::for('public-payments', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip().'|public-payments')
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many payment requests. Please wait before trying again.'));
        });

        RateLimiter::for('waitlist', function (Request $request) {
            $email = $this->normalizeIdentity($request->input('email'));

            return [
                Limit::perMinute(3)
                    ->by("waitlist:ip:".$request->ip())
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many waitlist requests from this network. Please wait before trying again.', "waitlist:ip:".$request->ip())),
                Limit::perHour(5)
                    ->by("waitlist:identity:{$email}")
                    ->response(fn () => $this->buildThrottleResponse($request, 'Too many waitlist requests for this email address. Please try again later.', "waitlist:identity:{$email}")),
            ];
        });

        RateLimiter::for('ai-generation', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip())
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many AI generation requests. Please wait before trying again.'));
        });

        RateLimiter::for('telemetry', function (Request $request) {
            $limit = max(1, (int) config('security.telemetry_event_limit_per_minute', 120));

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?? $this->requestFingerprint($request))
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many telemetry events. Please slow down.'));
        });

        RateLimiter::for('frontend-errors', function (Request $request) {
            $limit = max(1, (int) config('security.frontend_error_limit_per_minute', 30));

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?? $this->requestFingerprint($request))
                ->response(fn () => $this->buildThrottleResponse($request, 'Too many frontend error reports. Please slow down.'));
        });
    }

    private function configureAuthNotifications(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($user->email);
        });
    }

    private function checkDistributedRateLimitReadiness(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        if (! config('security.redis_required_in_production', true)) {
            return;
        }

        if (config('cache.default') !== 'redis') {
            Log::channel('security')->warning('Production cache store is not Redis. Distributed rate limiting is degraded.', [
                'cache_store' => config('cache.default'),
            ]);
        }
    }

    private function buildThrottleResponse(Request $request, string $message, ?string $identityKey = null)
    {
        SecurityIncidentRecorder::record($request, 'rate_limit_exceeded', 'warning', [
            'message' => $message,
        ], $identityKey);

        return response()->json([
            'message' => $message,
        ], 429);
    }

    private function normalizeIdentity(mixed $value): string
    {
        $normalized = Str::lower(trim((string) ($value ?: 'guest')));

        return $normalized !== '' ? $normalized : 'guest';
    }

    private function requestFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
        ]));
    }
}
