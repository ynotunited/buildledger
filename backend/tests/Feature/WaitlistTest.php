<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaitlistSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WaitlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_join_waitlist(): void
    {
        $this->postJson('/api/waitlist', [
            'name' => 'Taylor Tester',
            'email' => 'lead@example.com',
            'source' => 'homepage',
        ])->assertOk()
          ->assertJson([
              'message' => "You're on the waitlist. We'll email you when an invitation is ready.",
          ]);

        $this->assertDatabaseHas('waitlist_signups', [
            'name' => 'Taylor Tester',
            'email' => 'lead@example.com',
            'source' => 'homepage',
            'status' => WaitlistSignup::STATUS_PENDING,
        ]);
    }

    public function test_waitlist_deduplicates_by_email(): void
    {
        $this->postJson('/api/waitlist', [
            'name' => 'Taylor Tester',
            'email' => 'lead@example.com',
            'source' => 'homepage',
        ])->assertOk();

        $this->postJson('/api/waitlist', [
            'name' => 'Taylor Updated',
            'email' => 'lead@example.com',
            'source' => 'pricing-section',
        ])->assertOk();

        $this->assertDatabaseCount('waitlist_signups', 1);
        $this->assertDatabaseHas('waitlist_signups', [
            'name' => 'Taylor Updated',
            'email' => 'lead@example.com',
            'source' => 'pricing-section',
            'status' => WaitlistSignup::STATUS_PENDING,
        ]);
    }

    public function test_admin_can_approve_waitlist_signup(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $signup = WaitlistSignup::create([
            'name' => 'Taylor Tester',
            'email' => 'invite@example.com',
            'source' => 'homepage',
        ]);

        Notification::fake();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/admin/waitlist/{$signup->id}/approve")
            ->assertOk()
            ->assertJsonPath('waitlist_signup.status', WaitlistSignup::STATUS_APPROVED);

        $this->assertDatabaseHas('waitlist_signups', [
            'id' => $signup->id,
            'status' => WaitlistSignup::STATUS_APPROVED,
            'approved_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_can_toggle_invite_only_mode_off(): void
    {
        config(['security.invite_only' => true]);

        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/invite-mode', ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('invite_mode.enabled', false);

        $this->postJson('/api/register', [
            'name' => 'Open User',
            'email' => 'open@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertOk();
    }
}
