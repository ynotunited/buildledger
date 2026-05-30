<?php

namespace Tests\Feature;

use App\Models\ApplicationError;
use App\Models\BillingCheckout;
use App\Models\ImpersonationEvent;
use App\Models\Issue;
use App\Models\PaymentLedgerEntry;
use App\Models\Plan;
use App\Models\WaitlistSignup;
use App\Models\SecurityIncident;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'trial_ends_at' => now()->addDays(10),
        ]);
        $adminToken = $admin->createToken('test')->plainTextToken;

        $operator = User::factory()->create([
            'trial_ends_at' => now()->addDays(5),
        ]);
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        Subscription::create([
            'user_id' => $operator->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'current_period_starts_at' => now()->subDays(10),
            'current_period_ends_at' => now()->addDays(10),
        ]);

        BillingCheckout::create([
            'user_id' => $operator->id,
            'plan_id' => $growthPlan->id,
            'gateway' => 'paystack',
            'reference' => 'SUB-' . Str::upper(Str::random(10)),
            'amount_ngn' => 25000,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        Issue::create([
            'user_id' => $operator->id,
            'title' => 'Sample issue',
            'description' => 'Something needs review.',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'support',
        ]);

        SecurityIncident::create([
            'user_id' => $operator->id,
            'type' => 'rate_limit_triggered',
            'severity' => 'warning',
            'ip_address' => '127.0.0.1',
            'path' => '/api/login',
            'method' => 'POST',
            'identity_key' => 'admin:test',
            'occurred_at' => now(),
        ]);

        ApplicationError::create([
            'user_id' => $operator->id,
            'source' => 'frontend',
            'level' => 'error',
            'message' => 'Something went wrong.',
            'path' => '/dashboard',
            'occurred_at' => now(),
        ]);

        PaymentLedgerEntry::create([
            'user_id' => $operator->id,
            'event_type' => 'captured',
            'gateway' => 'paystack',
            'dedupe_key' => 'admin:test:captured',
            'amount' => 25000,
            'currency' => 'NGN',
            'occurred_at' => now(),
        ]);

        WaitlistSignup::create([
            'name' => 'Launch Lead',
            'email' => 'waitlist@example.com',
            'source' => 'homepage',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);

        $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('metrics.admin_users', 1)
            ->assertJsonPath('metrics.open_issues', 1)
            ->assertJsonPath('metrics.pending_checkouts', 1)
            ->assertJsonPath('metrics.completed_payments_30d', 1)
            ->assertJsonPath('metrics.waitlist_signups_total', 1)
            ->assertJsonStructure([
                'metrics',
                'plan_breakdown',
                'health',
                'recent_users',
                'recent_issues',
                'recent_security_incidents',
                'recent_errors',
                'recent_payments',
                'recent_waitlist_signups',
                'recent_subscriptions',
                'recent_support_sessions',
                'recent_operational_events',
                'invite_mode',
            ]);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_impersonate_owner_and_return_to_admin(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@madeitcodes.online',
            'password' => 'M4deItC0des!Admin#7Qv9',
        ]);
        $owner = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/login',
        ]);

        $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'M4deItC0des!Admin#7Qv9',
        ])->assertOk();

        $this->withHeader('Referer', 'http://localhost:3000/admin');
        $this->postJson("/api/admin/impersonate/{$owner->id}", [
            'note' => 'Investigate client billing issue.',
        ])
            ->assertOk()
            ->assertJsonPath('user.role', User::ROLE_OWNER)
            ->assertJsonPath('user.is_impersonating', true)
            ->assertJsonPath('user.impersonator_email', $admin->email);

        $this->assertDatabaseHas('impersonation_events', [
            'impersonator_user_id' => $admin->id,
            'target_user_id' => $owner->id,
            'action' => 'started',
            'note' => 'Investigate client billing issue.',
        ]);

        $this->actingAs($owner)
            ->withSession([
                'support_impersonator_user_id' => $admin->id,
                'support_impersonator_name' => $admin->name,
                'support_impersonator_email' => $admin->email,
                'support_impersonated_at' => now()->toIso8601String(),
            ])
            ->postJson('/api/admin/impersonation/stop')
            ->assertOk()
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->assertJsonPath('user.is_impersonating', false);

        $this->assertDatabaseHas('impersonation_events', [
            'impersonator_user_id' => $admin->id,
            'target_user_id' => $owner->id,
            'action' => 'stopped',
        ]);

        $this->assertSame(
            'Returned to admin account.',
            ImpersonationEvent::query()->latest('id')->first()?->note
        );
    }
}
