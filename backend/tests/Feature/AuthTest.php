<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WaitlistSignup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'requires_email_verification'])
                 ->assertJsonMissingPath('access_token')
                 ->assertJsonMissingPath('user.google_id')
                 ->assertJsonMissingPath('user.email_verification_token');

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_register_is_blocked_when_invite_only_and_email_is_not_approved(): void
    {
        config(['security.invite_only' => true]);

        $this->postJson('/api/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['email']);
    }

    public function test_register_is_allowed_when_waitlist_email_is_approved(): void
    {
        config(['security.invite_only' => true]);

        WaitlistSignup::create([
            'name' => 'Approved Lead',
            'email' => 'approved@example.com',
            'source' => 'homepage',
            'status' => WaitlistSignup::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => User::factory()->admin()->create()->id,
        ]);

        $this->postJson('/api/register', [
            'name' => 'Approved Lead',
            'email' => 'approved@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'approved@example.com']);
        $this->assertDatabaseHas('waitlist_signups', [
            'email' => 'approved@example.com',
            'status' => WaitlistSignup::STATUS_ACTIVATED,
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('SecurePass123!')]);

        $response = $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'requires_email_verification'])
                 ->assertJsonMissingPath('access_token')
                 ->assertJsonMissingPath('user.google_id')
                 ->assertJsonMissingPath('user.email_verification_token');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrong',
        ])->assertStatus(422);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
             ->postJson('/api/logout')
             ->assertStatus(200)
             ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_user_can_logout_from_session_auth_without_personal_token_delete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name'     => 'Another User',
            'email'    => 'taken@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertStatus(422);
    }

    public function test_forgot_password_response_does_not_disclose_account_existence(): void
    {
        $this->postJson('/api/forgot-password', [
            'email' => 'missing@example.com',
        ])->assertOk()
          ->assertJson([
              'message' => 'If the account exists, a password reset link has been sent.',
          ]);
    }

    public function test_email_verification_token_expires(): void
    {
        $plainTextToken = Str::random(64);
        $user = User::factory()->create([
            'email_verification_token' => hash('sha256', $plainTextToken),
            'email_verification_sent_at' => now()->subMinutes(61),
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/email/verify', [
            'email' => $user->email,
            'token' => $plainTextToken,
        ])->assertStatus(422);
    }

    public function test_authenticated_user_payload_only_exposes_needed_fields(): void
    {
        $user = User::factory()->create([
            'google_id' => 'google-123',
            'email_verification_token' => hash('sha256', Str::random(64)),
            'email_verification_sent_at' => now(),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('is_google_account', true)
            ->assertJsonMissingPath('google_id')
            ->assertJsonMissingPath('email_verification_token')
            ->assertJsonMissingPath('email_verification_sent_at');
    }

    public function test_unauthenticated_user_endpoint_returns_401(): void
    {
        $this->getJson('/api/user')
            ->assertStatus(401);
    }

    public function test_google_callback_blocks_unapproved_email_when_invite_only(): void
    {
        config(['security.invite_only' => true]);

        $state = Str::random(40);
        Cache::put("google_oauth_state:{$state}", true, now()->addMinutes(10));

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
            ], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'email' => 'new-google-user@example.com',
                'name' => 'New Google User',
                'sub' => 'google-sub-123',
            ], 200),
        ]);

        $this->getJson('/api/auth/google/callback?state=' . $state . '&code=fake-code')
            ->assertRedirect('http://localhost:3000/login?error=invite_required');
    }
}
