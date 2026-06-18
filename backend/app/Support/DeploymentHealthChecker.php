<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class DeploymentHealthChecker
{
    public function check(): array
    {
        $checks = [
            'app_key' => $this->checkAppKey(),
            'app_debug' => $this->checkAppDebug(),
            'https_urls' => $this->checkHttpsUrls(),
            'database' => $this->checkDatabase(),
            'row_level_security' => $this->checkRowLevelSecurity(),
            'redis_cache' => $this->checkRedisCache(),
            'session_cookie' => $this->checkSessionCookie(),
            'api_gateway' => $this->checkApiGateway(),
            'ops_flags' => $this->checkOperationsFlags(),
            'logs' => $this->checkLogs(),
        ];

        return [
            'status' => $this->overallStatus($checks),
            'checks' => $checks,
        ];
    }

    private function checkAppKey(): array
    {
        $appKey = (string) config('app.key');

        return $appKey !== ''
            ? $this->pass('Application key is configured.')
            : $this->fail('APP_KEY is missing.');
    }

    private function checkAppDebug(): array
    {
        if (! app()->environment('production')) {
            return config('app.debug')
                ? $this->pass('Debug mode is enabled outside production.')
                : $this->pass('Debug mode is disabled.');
        }

        return config('app.debug')
            ? $this->warn('APP_DEBUG should be false in production.')
            : $this->pass('Debug mode is disabled.');
    }

    private function checkHttpsUrls(): array
    {
        $appUrl = (string) config('app.url');
        $frontendUrl = (string) config('app.frontend_url');

        if ($appUrl === '' || $frontendUrl === '') {
            return $this->warn('APP_URL or FRONTEND_URL is missing.');
        }

        if (! app()->environment('production')) {
            return $this->pass('URL configuration is acceptable outside production.');
        }

        if (! Str::startsWith($appUrl, 'https://') || ! Str::startsWith($frontendUrl, 'https://')) {
            return $this->warn('APP_URL and FRONTEND_URL should use HTTPS.');
        }

        return $this->pass('Application URLs use HTTPS.');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->pass('Database connection is healthy.');
        } catch (Throwable $exception) {
            return $this->fail('Database connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function checkRowLevelSecurity(): array
    {
        $required = (bool) config('security.row_level_security_required', true);
        $driver = DB::connection()->getDriverName();

        if (! $required) {
            return $this->pass('Row level security enforcement is optional in this environment.', [
                'driver' => $driver,
            ]);
        }

        if ($driver !== 'pgsql') {
            return app()->environment('production')
                ? $this->fail('Row level security requires PostgreSQL in production.', [
                    'driver' => $driver,
                ])
                : $this->warn('Row level security is only enforced on PostgreSQL.', [
                    'driver' => $driver,
                ]);
        }

        return $this->pass('Row level security is available on PostgreSQL.', [
            'driver' => $driver,
        ]);
    }

    private function checkRedisCache(): array
    {
        $redisRequired = (bool) config('security.redis_required_in_production', true);
        $cacheStore = (string) config('cache.default');

        if (! app()->environment('production') && $cacheStore !== 'redis') {
            return $this->pass('Redis is not required outside production.');
        }

        if (! $redisRequired && $cacheStore !== 'redis') {
            return $this->pass('Redis is optional in this environment.');
        }

        if ($cacheStore !== 'redis') {
            return $this->fail('CACHE_STORE must be set to redis for distributed throttling.');
        }

        try {
            Cache::store('redis')->put('deployment-health-check', 'ok', 10);
            $value = Cache::store('redis')->get('deployment-health-check');

            if ($value !== 'ok') {
                return $this->fail('Redis cache write/read probe failed.');
            }

            return $this->pass('Redis cache is healthy.');
        } catch (Throwable $exception) {
            return $this->fail('Redis cache connection failed.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function checkSessionCookie(): array
    {
        if (! app()->environment('production')) {
            return $this->pass('Secure-cookie enforcement is relaxed outside production.');
        }

        return config('session.secure')
            ? $this->pass('Secure session cookies are enabled.')
            : $this->warn('SESSION_SECURE_COOKIE should be true in production.');
    }

    private function checkApiGateway(): array
    {
        if (! app()->environment('production')) {
            return $this->pass('API gateway enforcement is optional outside production.');
        }

        if (! config('security.api_gateway_enforced')) {
            return $this->pass('API gateway enforcement is not enabled in this deployment.', [
                'enabled' => false,
            ]);
        }

        $sharedSecret = (string) config('security.api_gateway_shared_secret');
        $allowedHosts = config('security.api_gateway_allowed_hosts', []);

        if ($sharedSecret === '') {
            return $this->fail('API gateway enforcement is enabled but API_GATEWAY_SHARED_SECRET is missing.');
        }

        if ($allowedHosts === []) {
            return $this->warn('API gateway enforcement is enabled but allowed hosts are not set.');
        }

        return $this->pass('API gateway enforcement is configured.');
    }

    private function checkOperationsFlags(): array
    {
        $flags = [
            'payments_enabled' => (bool) config('ops.payments_enabled', true),
            'webhooks_enabled' => (bool) config('ops.webhooks_enabled', true),
            'backups_enabled' => (bool) config('ops.backups_enabled', true),
            'alerts_enabled' => (bool) config('ops.alerts_enabled', true),
            'reconciliation_enabled' => (bool) config('ops.reconciliation_enabled', true),
        ];

        if (! app()->environment('production')) {
            return $this->pass('Operational launch controls are available outside production.', $flags);
        }

        $disabled = collect($flags)->filter(fn (bool $enabled) => ! $enabled)->keys()->values()->all();

        if ($disabled !== []) {
            return $this->warn('One or more operational launch controls are disabled.', [
                'disabled' => $disabled,
                'flags' => $flags,
            ]);
        }

        return $this->pass('Operational launch controls are enabled.', $flags);
    }

    private function checkLogs(): array
    {
        $configuredStack = config('logging.channels.stack.channels', []);
        $stack = collect(is_array($configuredStack) ? $configuredStack : explode(',', (string) $configuredStack))
            ->map(fn (string $channel) => trim($channel))
            ->filter()
            ->values();

        $required = collect(['daily', 'auth', 'api', 'security']);
        $missing = $required->diff($stack)->values()->all();

        if ($missing !== []) {
            return $this->warn('Log stack is missing required channels.', [
                'missing' => $missing,
            ]);
        }

        return $this->pass('Required log channels are present.');
    }

    private function overallStatus(array $checks): string
    {
        if (collect($checks)->contains(fn (array $check) => $check['status'] === 'fail')) {
            return 'failed';
        }

        if (collect($checks)->contains(fn (array $check) => $check['status'] === 'warn')) {
            return 'warning';
        }

        return 'ok';
    }

    private function pass(string $message, array $context = []): array
    {
        return [
            'status' => 'pass',
            'message' => $message,
            'context' => $context,
        ];
    }

    private function warn(string $message, array $context = []): array
    {
        return [
            'status' => 'warn',
            'message' => $message,
            'context' => $context,
        ];
    }

    private function fail(string $message, array $context = []): array
    {
        return [
            'status' => 'fail',
            'message' => $message,
            'context' => $context,
        ];
    }
}
