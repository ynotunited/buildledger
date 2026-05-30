<?php

namespace App\Http\Middleware;

use App\Support\SecurityIncidentRecorder;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestSecurityMonitor
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isBlocked($request)) {
            return response()->json([
                'message' => 'Too many suspicious requests. Please try again later.',
            ], 429);
        }

        $this->inspectRequestContent($request);
        $this->logSuspiciousRequestPatterns($request);
        $this->logTrafficSpikes($request);

        $response = $next($request);
        $this->trackFailureBursts($request, $response);

        if ($response->getStatusCode() >= 500) {
            Log::channel('api')->error('API/server response returned a 5xx status.', [
                'status' => $response->getStatusCode(),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'request_id' => $request->headers->get('X-Request-Id'),
            ]);
        }

        return $response;
    }

    private function logSuspiciousRequestPatterns(Request $request): void
    {
        $path = strtolower($request->path());
        $fragments = config('security.scan_path_fragments', []);
        $ip = $request->ip() ?? 'unknown';

        foreach ($fragments as $fragment) {
            if (str_contains($path, strtolower($fragment))) {
                $counterKey = "security:scan:{$ip}";
                $count = Cache::increment($counterKey);

                if ($count === 1) {
                    Cache::put($counterKey, 1, now()->addMinutes(10));
                }

                SecurityIncidentRecorder::record($request, 'scan_like_request', 'warning', [
                    'query' => $request->query(),
                    'count' => $count,
                ]);

                if ($count >= 5) {
                    $this->blockIp($ip, 'Repeated scan-like requests detected.');
                }

                return;
            }
        }
    }

    private function logTrafficSpikes(Request $request): void
    {
        $ip = $request->ip() ?? 'unknown';
        $window = now()->format('YmdHi');
        $counterKey = "security:requests:{$ip}:{$window}";
        $logKey = "security:requests:logged:{$ip}:{$window}";

        $count = Cache::increment($counterKey);

        if ($count === 1) {
            Cache::put($counterKey, 1, now()->addMinutes(2));
        }

        $threshold = max(1, (int) config('security.suspicious_requests_per_minute', 120));

        if ($count >= $threshold && Cache::add($logKey, true, now()->addMinutes(2))) {
            SecurityIncidentRecorder::record($request, 'traffic_spike', 'warning', [
                'count' => $count,
            ], 'ip:'.$ip);
        }

        if ($count >= ($threshold * 3)) {
            $this->blockIp($ip, 'Temporary cooldown applied after extreme request volume.');
        }
    }

    private function isBlocked(Request $request): bool
    {
        $ip = $request->ip() ?? 'unknown';

        return Cache::has("security:blocked:{$ip}");
    }

    private function blockIp(string $ip, string $reason): void
    {
        $key = "security:blocked:{$ip}";
        $duration = max(1, (int) config('security.waf_block_duration_minutes', 10));

        if (Cache::add($key, true, now()->addMinutes($duration))) {
            Log::channel('security')->warning('Temporary IP cooldown applied.', [
                'ip' => $ip,
                'reason' => $reason,
            ]);
        }
    }

    private function inspectRequestContent(Request $request): void
    {
        $payload = strtolower(json_encode($request->all()) ?: '');
        $query = strtolower(http_build_query($request->query()));
        $target = $payload.' '.$query.' '.strtolower($request->path());

        foreach (config('security.waf_signatures', []) as $signature) {
            if ($signature !== '' && str_contains($target, strtolower($signature))) {
                SecurityIncidentRecorder::record($request, 'waf_signature_match', 'warning', [
                    'signature' => $signature,
                ]);

                $counterKey = 'security:waf:'.$request->ip();
                $count = Cache::increment($counterKey);
                if ($count === 1) {
                    Cache::put($counterKey, 1, now()->addMinutes(10));
                }

                if ($count >= 3) {
                    $this->blockIp($request->ip() ?? 'unknown', 'Repeated WAF signature matches.');
                }

                break;
            }
        }
    }

    private function trackFailureBursts(Request $request, Response $response): void
    {
        if (! in_array($response->getStatusCode(), [401, 403, 404, 422, 429], true)) {
            return;
        }

        $ip = $request->ip() ?? 'unknown';
        $window = now()->format('YmdHi');
        $counterKey = "security:failures:{$ip}:{$window}";
        $count = Cache::increment($counterKey);

        if ($count === 1) {
            Cache::put($counterKey, 1, now()->addMinutes(2));
        }

        $threshold = max(1, (int) config('security.suspicious_failures_per_minute', 25));

        if ($count >= $threshold) {
            SecurityIncidentRecorder::record($request, 'failure_burst', 'warning', [
                'status' => $response->getStatusCode(),
                'count' => $count,
            ], 'ip:'.$ip);
        }
    }
}
