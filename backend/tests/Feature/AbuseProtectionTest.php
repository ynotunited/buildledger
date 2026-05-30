<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.1']);

        User::factory()->create([
            'email' => 'owner@example.com',
            'password' => 'password123!',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/login', [
                'email' => 'owner@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/login', [
            'email' => 'owner@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_registration_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.2']);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson('/api/register', [
                'name' => "User {$attempt}",
                'email' => "user{$attempt}@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertStatus(200);
        }

        $this->postJson('/api/register', [
            'name' => 'User 4',
            'email' => 'user4@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(429);
    }

    public function test_notification_polling_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.3']);

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->getJson('/api/notifications/unread')->assertStatus(200);
        }

        $this->getJson('/api/notifications/unread')->assertStatus(429);
    }

    public function test_public_contract_access_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.0.4']);

        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        $contract = Contract::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);

        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this->getJson("/api/public/contracts/{$contract->signing_token}")
                ->assertStatus(200);
        }

        $this->getJson("/api/public/contracts/{$contract->signing_token}")
            ->assertStatus(429);
    }
}
