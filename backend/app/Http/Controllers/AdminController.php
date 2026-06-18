<?php

namespace App\Http\Controllers;

use App\Models\ApplicationError;
use App\Models\Client;
use App\Models\AnalyticsEvent;
use App\Models\BillingCheckout;
use App\Models\Issue;
use App\Models\ImpersonationEvent;
use App\Models\OperationalEvent;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\Plan;
use App\Models\WaitlistSignup;
use App\Models\SecurityIncident;
use App\Models\Subscription;
use App\Models\User;
use App\Support\InviteModeManager;
use App\Support\DeploymentHealthChecker;
use App\Support\InputSanitizer;
use App\Support\PaymentLedger;
use App\Notifications\WaitlistInvitationApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    private const IMPERSONATOR_ID_KEY = 'support_impersonator_user_id';
    private const IMPERSONATOR_NAME_KEY = 'support_impersonator_name';
    private const IMPERSONATOR_EMAIL_KEY = 'support_impersonator_email';
    private const IMPERSONATED_AT_KEY = 'support_impersonated_at';

    public function index(Request $request)
    {
        $now = now();
        $last7Days = $now->copy()->subDays(7);
        $last30Days = $now->copy()->subDays(30);

        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'code' => $plan->code,
                    'name' => $plan->name,
                    'monthly_price_ngn' => (int) $plan->price_ngn,
                    'annual_price_ngn' => (int) ($plan->price_annually_ngn ?? ($plan->price_ngn * 12)),
                    'active_subscriptions' => $plan->subscriptions()->where('status', 'active')->count(),
                    'cancelled_subscriptions' => $plan->subscriptions()->where('status', 'cancelled')->count(),
                    'expired_subscriptions' => $plan->subscriptions()->where('status', 'expired')->count(),
                ];
            });

        $metrics = [
            'total_users' => User::query()->count(),
            'admin_users' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            'owner_users' => User::query()->where('role', User::ROLE_OWNER)->count(),
            'client_users' => User::query()->where('role', User::ROLE_CLIENT)->count(),
            'total_clients' => Client::query()->count(),
            'active_clients' => Client::query()->where('status', 'Active')->count(),
            'trial_accounts' => User::query()
                ->where(function ($query) use ($now) {
                    $query->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>=', $now);
                })
                ->count(),
            'expiring_trials_7d' => User::query()
                ->whereNotNull('trial_ends_at')
                ->whereBetween('trial_ends_at', [$now, $now->copy()->addDays(7)])
                ->count(),
            'active_subscriptions' => Subscription::query()->where('status', 'active')->count(),
            'due_renewals_7d' => Subscription::query()
                ->where('status', 'active')
                ->whereNotNull('current_period_ends_at')
                ->whereBetween('current_period_ends_at', [$now, $now->copy()->addDays(7)])
                ->count(),
            'pending_checkouts' => BillingCheckout::query()->where('status', 'pending')->count(),
            'failed_checkouts' => BillingCheckout::query()->where('status', 'failed')->count(),
            'revenue_30d' => app(PaymentLedger::class)->netCapturedSince($last30Days),
            'open_issues' => Issue::query()->whereIn('status', ['open', 'in_progress'])->count(),
            'issues_7d' => Issue::query()->where('created_at', '>=', $last7Days)->count(),
            'security_incidents_7d' => SecurityIncident::query()->where('occurred_at', '>=', $last7Days)->count(),
            'errors_7d' => ApplicationError::query()
                ->where('level', 'error')
                ->where('occurred_at', '>=', $last7Days)
                ->count(),
            'analytics_events_30d' => AnalyticsEvent::query()->where('occurred_at', '>=', $last30Days)->count(),
            'completed_payments_30d' => PaymentLedgerEntry::query()
                ->where('event_type', 'captured')
                ->where('occurred_at', '>=', $last30Days)
                ->count(),
            'waitlist_signups_total' => WaitlistSignup::query()->count(),
            'waitlist_signups_30d' => WaitlistSignup::query()->where('created_at', '>=', $last30Days)->count(),
            'operational_events_7d' => OperationalEvent::query()->where('occurred_at', '>=', $last7Days)->count(),
            'backup_events_7d' => OperationalEvent::query()->where('category', 'backup')->where('occurred_at', '>=', $last7Days)->count(),
            'reconciliation_events_7d' => OperationalEvent::query()->where('category', 'reconciliation')->where('occurred_at', '>=', $last7Days)->count(),
        ];

        $recentUsers = User::query()
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'trial_ends_at' => $user->trial_ends_at,
                'created_at' => $user->created_at,
            ]);

        $recentClients = Client::query()
            ->with('user:id,name,email,role')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'company' => $client->company,
                'status' => $client->status,
                'email' => $client->email,
                'phone' => $client->phone,
                'owner_name' => $client->user?->name,
                'owner_email' => $client->user?->email,
                'owner_role' => $client->user?->role,
                'created_at' => $client->created_at,
            ]);

        $recentIssues = Issue::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Issue $issue) => [
                'id' => $issue->id,
                'title' => $issue->title,
                'status' => $issue->status,
                'priority' => $issue->priority,
                'category' => $issue->category,
                'user_name' => $issue->user?->name,
                'user_email' => $issue->user?->email,
                'created_at' => $issue->created_at,
            ]);

        $recentSecurityIncidents = SecurityIncident::query()
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (SecurityIncident $incident) => [
                'id' => $incident->id,
                'type' => $incident->type,
                'severity' => $incident->severity,
                'path' => $incident->path,
                'method' => $incident->method,
                'identity_key' => $incident->identity_key,
                'created_at' => $incident->occurred_at ?? $incident->created_at,
            ]);

        $recentErrors = ApplicationError::query()
            ->where('level', 'error')
            ->where('occurred_at', '>=', $last7Days)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ApplicationError $error) => [
                'id' => $error->id,
                'message' => $error->message,
                'level' => $error->level,
                'source' => $error->source,
                'path' => $error->path,
                'created_at' => $error->occurred_at ?? $error->created_at,
            ]);

        $recentPayments = Payment::query()
            ->with([
                'user:id,name,email',
                'invoice:id,invoice_number,total',
                'latestLedgerEntry',
            ])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'reference' => $payment->gateway_reference,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'gateway' => $payment->gateway,
                'user_name' => $payment->user?->name,
                'invoice_number' => $payment->invoice?->invoice_number,
                'invoice_total' => $payment->invoice?->total,
                'created_at' => $payment->created_at,
            ]);

        $recentWaitlistSignups = WaitlistSignup::query()
            ->with('approvedBy:id,name,email')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (WaitlistSignup $signup) => [
                'id' => $signup->id,
                'name' => $signup->name,
                'email' => $signup->email,
                'source' => $signup->source,
                'status' => $signup->status,
                'approved_at' => $signup->approved_at,
                'activated_at' => $signup->activated_at,
                'rejected_at' => $signup->rejected_at,
                'approved_by_name' => $signup->approvedBy?->name,
                'ip_address' => $signup->ip_address,
                'created_at' => $signup->created_at,
            ]);

        $recentSupportSessions = ImpersonationEvent::query()
            ->with([
                'impersonator:id,name,email',
                'target:id,name,email,role',
            ])
            ->latest('occurred_at')
            ->limit(5)
            ->get()
            ->map(fn (ImpersonationEvent $event) => [
                'id' => $event->id,
                'action' => $event->action,
                'note' => $event->note,
                'ip_address' => $event->ip_address,
                'user_agent' => $event->user_agent,
                'impersonator_name' => $event->impersonator?->name,
                'impersonator_email' => $event->impersonator?->email,
                'target_name' => $event->target?->name,
                'target_email' => $event->target?->email,
                'target_role' => $event->target?->role,
                'occurred_at' => $event->occurred_at,
            ]);

        $recentOperationalEvents = OperationalEvent::query()
            ->with('user:id,name,email,role')
            ->latest('occurred_at')
            ->limit(5)
            ->get()
            ->map(fn (OperationalEvent $event) => [
                'id' => $event->id,
                'category' => $event->category,
                'severity' => $event->severity,
                'title' => $event->title,
                'message' => $event->message,
                'source' => $event->source,
                'reference_type' => $event->reference_type,
                'reference_id' => $event->reference_id,
                'user_name' => $event->user?->name,
                'user_email' => $event->user?->email,
                'resolved_at' => $event->resolved_at,
                'occurred_at' => $event->occurred_at,
            ]);

        $recentSubscriptions = Subscription::query()
            ->with([
                'user:id,name,email,role',
                'plan:id,code,name',
            ])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Subscription $subscription) => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'billing_interval' => $subscription->billing_interval,
                'gateway' => $subscription->gateway,
                'current_period_ends_at' => $subscription->current_period_ends_at,
                'user_name' => $subscription->user?->name,
                'user_email' => $subscription->user?->email,
                'plan_name' => $subscription->plan?->name,
                'plan_code' => $subscription->plan?->code,
            ]);

        return response()->json([
            'metrics' => $metrics,
            'plan_breakdown' => $plans,
            'health' => app(DeploymentHealthChecker::class)->check(),
            'recent_users' => $recentUsers,
            'recent_clients' => $recentClients,
            'recent_issues' => $recentIssues,
            'recent_security_incidents' => $recentSecurityIncidents,
            'recent_errors' => $recentErrors,
            'recent_payments' => $recentPayments,
            'recent_waitlist_signups' => $recentWaitlistSignups,
            'recent_subscriptions' => $recentSubscriptions,
            'recent_support_sessions' => $recentSupportSessions,
            'recent_operational_events' => $recentOperationalEvents,
            'invite_mode' => [
                'enabled' => app(InviteModeManager::class)->isInviteOnly(),
                'source' => app(InviteModeManager::class)->source(),
                'updated_at' => app(InviteModeManager::class)->updatedAt(),
            ],
        ]);
    }

    public function startImpersonation(Request $request, User $user)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_if($user->isAdmin(), 422, 'Admin accounts cannot be impersonated.');

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $impersonator = $request->user();
        $note = InputSanitizer::multilineText($validated['note'] ?? null);
        $note = $note !== '' ? $note : null;

        $this->ensureSessionStore($request);

        $request->session()->put([
            self::IMPERSONATOR_ID_KEY => $impersonator->id,
            self::IMPERSONATOR_NAME_KEY => $impersonator->name,
            self::IMPERSONATOR_EMAIL_KEY => $impersonator->email,
            self::IMPERSONATED_AT_KEY => now()->toIso8601String(),
        ]);

        auth()->guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->recordImpersonationEvent(
            impersonator: $impersonator,
            target: $user,
            action: 'started',
            request: $request,
            note: $note,
            context: [
                'impersonation_keys' => [
                    self::IMPERSONATOR_ID_KEY,
                    self::IMPERSONATOR_NAME_KEY,
                    self::IMPERSONATOR_EMAIL_KEY,
                    self::IMPERSONATED_AT_KEY,
                ],
            ]
        );

        $this->logSupportEvent('info', 'Impersonation started.', $request, $user, [
            'impersonator_id' => $impersonator->id,
            'impersonator_email' => $impersonator->email,
            'support_note' => $note,
        ]);

        return response()->json([
            'message' => 'Now viewing as the selected user.',
            'user' => (new \App\Http\Resources\AuthUserResource($user->fresh()))->resolve(),
        ]);
    }

    public function stopImpersonation(Request $request)
    {
        $this->ensureSessionStore($request);
        $target = $request->user();

        $impersonatorId = (int) $request->session()->pull(self::IMPERSONATOR_ID_KEY, 0);

        if (! $impersonatorId) {
            return response()->json([
                'message' => 'You are not currently impersonating another user.',
            ]);
        }

        $impersonator = User::query()->find($impersonatorId);

        if (! $impersonator || ! $impersonator->isAdmin()) {
            $request->session()->forget([
                self::IMPERSONATOR_NAME_KEY,
                self::IMPERSONATOR_EMAIL_KEY,
                self::IMPERSONATED_AT_KEY,
            ]);

            return response()->json([
                'message' => 'Impersonation session ended.',
            ]);
        }

        auth()->guard('web')->login($impersonator);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $request->session()->forget([
            self::IMPERSONATOR_NAME_KEY,
            self::IMPERSONATOR_EMAIL_KEY,
            self::IMPERSONATED_AT_KEY,
        ]);

        $this->recordImpersonationEvent(
            impersonator: $impersonator,
            target: $target,
            action: 'stopped',
            request: $request,
            note: 'Returned to admin account.'
        );

        $this->logSupportEvent('info', 'Impersonation stopped.', $request, $impersonator);

        return response()->json([
            'message' => 'Returned to your admin account.',
            'user' => (new \App\Http\Resources\AuthUserResource($impersonator->fresh()))->resolve(),
        ]);
    }

    public function approveWaitlistSignup(Request $request, WaitlistSignup $waitlistSignup)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($waitlistSignup->status === WaitlistSignup::STATUS_ACTIVATED) {
            return response()->json([
                'message' => 'This invite has already been activated.',
                'waitlist_signup' => $this->formatWaitlistSignup($waitlistSignup->fresh(['approvedBy:id,name,email'])),
            ]);
        }

        $waitlistSignup->forceFill([
            'status' => WaitlistSignup::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $request->user()->id,
            'rejected_at' => null,
        ])->save();

        $waitlistSignup->refresh()->load('approvedBy:id,name,email');

        Notification::route('mail', $waitlistSignup->email)
            ->notify(new WaitlistInvitationApproved($waitlistSignup, $request->user()->name));

        $this->logSupportEvent('info', 'Waitlist invite approved.', $request, $request->user(), [
            'waitlist_email' => $waitlistSignup->email,
            'waitlist_status' => $waitlistSignup->status,
        ]);

        return response()->json([
            'message' => 'Invitation sent.',
            'waitlist_signup' => $this->formatWaitlistSignup($waitlistSignup),
        ]);
    }

    public function rejectWaitlistSignup(Request $request, WaitlistSignup $waitlistSignup)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $waitlistSignup->forceFill([
            'status' => WaitlistSignup::STATUS_REJECTED,
            'rejected_at' => now(),
            'approved_at' => null,
            'approved_by_user_id' => null,
        ])->save();

        $waitlistSignup->refresh()->load('approvedBy:id,name,email');

        $this->logSupportEvent('info', 'Waitlist invite rejected.', $request, $request->user(), [
            'waitlist_email' => $waitlistSignup->email,
            'waitlist_status' => $waitlistSignup->status,
        ]);

        return response()->json([
            'message' => 'Waitlist signup rejected.',
            'waitlist_signup' => $this->formatWaitlistSignup($waitlistSignup),
        ]);
    }

    public function setInviteMode(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        app(InviteModeManager::class)->setInviteOnly((bool) $validated['enabled'], $request->user());

        return response()->json([
            'message' => $validated['enabled']
                ? 'Invite-only mode enabled.'
                : 'Open registration enabled.',
            'invite_mode' => [
                'enabled' => (bool) $validated['enabled'],
                'source' => 'database',
                'updated_at' => now(),
            ],
        ]);
    }

    private function logSupportEvent(
        string $level,
        string $message,
        Request $request,
        User $user,
        array $context = []
    ): void {
        Log::channel('auth')->log($level, $message, array_merge([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user->id,
            'email' => $user->email,
        ], $context));
    }

    private function recordImpersonationEvent(
        User $impersonator,
        User $target,
        string $action,
        Request $request,
        ?string $note = null,
        array $context = []
    ): void {
        ImpersonationEvent::create([
            'impersonator_user_id' => $impersonator->id,
            'target_user_id' => $target->id,
            'action' => $action,
            'note' => $note,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'context' => $context ?: null,
            'occurred_at' => now(),
        ]);
    }

    private function formatWaitlistSignup(WaitlistSignup $signup): array
    {
        return [
            'id' => $signup->id,
            'name' => $signup->name,
            'email' => $signup->email,
            'source' => $signup->source,
            'status' => $signup->status,
            'approved_at' => $signup->approved_at,
            'activated_at' => $signup->activated_at,
            'rejected_at' => $signup->rejected_at,
            'approved_by_name' => $signup->approvedBy?->name,
            'ip_address' => $signup->ip_address,
            'created_at' => $signup->created_at,
        ];
    }
}
