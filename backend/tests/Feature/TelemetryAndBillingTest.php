<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PaymentLedgerEntry;
use App\Models\Subscription;
use App\Models\BillingCheckout;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelemetryAndBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $token = $this->user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_can_capture_analytics_event(): void
    {
        $this->postJson('/api/telemetry/events', [
            'event_name' => 'page_view',
            'path' => '/dashboard',
            'source' => 'frontend',
            'session_id' => 'session-1',
            'properties' => ['section' => 'hero'],
        ])->assertStatus(202);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'page_view',
            'path' => '/dashboard',
        ]);
    }

    public function test_starter_plan_hits_paywall_for_analytics_summary(): void
    {
        $this->getJson('/api/analytics/summary')
            ->assertStatus(402)
            ->assertJsonPath('requires_upgrade', true);
    }

    public function test_can_start_free_trial_from_billing_checkout(): void
    {
        $this->postJson('/api/billing/checkout', [
            'plan_code' => 'starter',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk()
            ->assertJsonPath('current_plan.code', 'starter');

        $this->assertNotNull($this->user->fresh()->trial_ends_at);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_can_checkout_paid_plan_with_proration_and_annual_interval(): void
    {
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'current_period_starts_at' => now()->subDays(15),
            'current_period_ends_at' => now()->addDays(15),
            'metadata' => [
                'payment_token' => 'AUTH_123',
            ],
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/checkout',
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/billing/checkout', [
            'plan_code' => 'growth',
            'gateway' => 'paystack',
            'billing_interval' => 'annual',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $response->assertOk()
            ->assertJsonPath('authorization_url', 'https://paystack.test/checkout');

        $this->assertGreaterThan(0, (int) $response->json('proration_credit_ngn'));
        $this->assertLessThan($growthPlan->price_annually_ngn, (int) $response->json('amount_due_ngn'));
    }

    public function test_billing_checkout_reuses_idempotency_key_for_duplicate_requests(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/checkout',
                ],
            ], 200),
        ]);

        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();
        $idempotencyKey = (string) Str::uuid();

        $first = $this->postJson('/api/billing/checkout', [
            'plan_code' => $growthPlan->code,
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'idempotency_key' => $idempotencyKey,
        ]);

        $second = $this->postJson('/api/billing/checkout', [
            'plan_code' => $growthPlan->code,
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'idempotency_key' => $idempotencyKey,
        ]);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('reference'), $second->json('reference'));
        $this->assertDatabaseCount('billing_checkouts', 1);
    }

    public function test_can_cancel_active_subscription(): void
    {
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'current_period_starts_at' => now()->subDays(5),
            'current_period_ends_at' => now()->addDays(25),
        ]);

        $response = $this->postJson('/api/billing/cancel');

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'cancelled');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_expired_subscriptions_are_swept_by_command(): void
    {
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        Artisan::call('subscriptions:expire');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'status' => 'expired',
        ]);
    }

    public function test_subscription_renewal_command_extends_paid_plan(): void
    {
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subMinute(),
            'metadata' => [
                'payment_token' => 'AUTH_123',
            ],
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/charge_authorization' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'id' => 1001,
                ],
            ], 200),
        ]);

        Artisan::call('subscriptions:renew');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
        ]);

        $this->assertTrue(
            $subscription->fresh()->current_period_ends_at->isAfter(now())
        );
    }

    public function test_subscription_webhook_extends_paid_plan(): void
    {
        $growthPlan = Plan::query()->where('code', 'growth')->firstOrFail();

        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'status' => 'active',
            'gateway' => 'paystack',
            'billing_interval' => 'monthly',
            'current_period_starts_at' => now()->subMonth(),
            'current_period_ends_at' => now()->subMinute(),
            'metadata' => [
                'payment_token' => 'AUTH_123',
            ],
        ]);

        $checkout = BillingCheckout::create([
            'user_id' => $this->user->id,
            'plan_id' => $growthPlan->id,
            'gateway' => 'paystack',
            'reference' => 'SUB-RENEW-1',
            'amount_ngn' => 25000,
            'billing_interval' => 'monthly',
            'status' => 'pending',
            'expires_at' => now()->addHour(),
            'metadata' => [
                'kind' => 'subscription_renewal',
                'subscription_id' => $subscription->id,
                'user_id' => $this->user->id,
                'plan_code' => $growthPlan->code,
                'billing_interval' => 'monthly',
            ],
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $checkout->reference,
                'status' => 'success',
                'id' => 2001,
                'authorization' => [
                    'authorization_code' => 'AUTH_123',
                    'customer_code' => 'CUS_123',
                    'reusable' => true,
                ],
            ],
        ];

        $signature = hash_hmac('sha512', json_encode($payload), config('services.paystack.secret_key'));

        $this->withHeader('x-paystack-signature', $signature)
            ->postJson('/api/webhooks/paystack', $payload)
            ->assertOk();

        $this->assertDatabaseHas('billing_checkouts', [
            'id' => $checkout->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('payment_ledger_entries', [
            'billing_checkout_id' => $checkout->id,
            'event_type' => 'captured',
        ]);

        $this->assertTrue(
            $subscription->fresh()->current_period_ends_at->isAfter(now())
        );
    }

    public function test_can_create_issue(): void
    {
        $this->postJson('/api/issues', [
            'title' => 'Need help with invoice branding',
            'description' => 'The company logo does not appear on one invoice.',
            'priority' => 'high',
            'category' => 'support',
        ])->assertStatus(201)
            ->assertJsonPath('data.priority', 'high');

        $this->assertDatabaseHas('issues', [
            'user_id' => $this->user->id,
            'category' => 'support',
        ]);
    }
}
