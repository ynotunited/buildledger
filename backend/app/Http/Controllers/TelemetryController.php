<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Support\ApplicationErrorRecorder;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TelemetryController extends Controller
{
    public function captureEvent(Request $request)
    {
        $validated = $request->validate([
            'event_name' => 'required|string|max:100',
            'path' => 'nullable|string|max:255',
            'source' => ['nullable', Rule::in(['frontend', 'backend'])],
            'session_id' => 'nullable|string|max:100',
            'properties' => 'nullable|array|max:25',
        ]);

        $event = AnalyticsEvent::create([
            'user_id' => $request->user()?->id,
            'session_id' => $validated['session_id'] ?? null,
            'event_name' => InputSanitizer::text($validated['event_name']),
            'path' => isset($validated['path']) ? InputSanitizer::text($validated['path']) : null,
            'source' => $validated['source'] ?? 'frontend',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'properties' => $validated['properties'] ?? [],
            'occurred_at' => now(),
        ]);

        Log::channel('analytics')->info('Analytics event captured.', [
            'event_name' => $event->event_name,
            'user_id' => $event->user_id,
            'path' => $event->path,
        ]);

        return response()->json(['message' => 'Event captured.'], 202);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $plan = $user->currentPlan();

        $events = AnalyticsEvent::query()
            ->where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDays(30));

        return response()->json([
            'current_plan' => $plan?->code,
            'totals' => [
                'events_30d' => (clone $events)->count(),
                'page_views_30d' => (clone $events)->where('event_name', 'page_view')->count(),
                'payment_events_30d' => (clone $events)->where('event_name', 'payment_completed')->count(),
            ],
            'top_events' => (clone $events)
                ->selectRaw('event_name, COUNT(*) as aggregate')
                ->groupBy('event_name')
                ->orderByDesc('aggregate')
                ->limit(8)
                ->get(),
        ]);
    }

    public function captureFrontendError(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'path' => 'nullable|string|max:255',
            'stack' => 'nullable|string|max:20000',
            'component_stack' => 'nullable|string|max:20000',
            'context' => 'nullable|array|max:25',
        ]);

        ApplicationErrorRecorder::record([
            'user_id' => $request->user()?->id,
            'source' => 'frontend',
            'level' => 'error',
            'message' => InputSanitizer::text($validated['message']),
            'exception_class' => null,
            'path' => isset($validated['path']) ? InputSanitizer::text($validated['path']) : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_id' => $request->headers->get('X-Request-Id'),
            'context' => array_filter([
                'stack' => $validated['stack'] ?? null,
                'component_stack' => $validated['component_stack'] ?? null,
                'extra' => $validated['context'] ?? [],
            ]),
            'occurred_at' => now(),
        ]);

        return response()->json(['message' => 'Error report captured.'], 202);
    }
}
