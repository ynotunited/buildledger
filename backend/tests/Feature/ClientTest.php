<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");
        return $user;
    }

    public function test_can_list_clients(): void
    {
        $user = $this->actingAsUser();
        Client::factory()->count(3)->create(['user_id' => $user->id]);

        $this->getJson('/api/clients')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_list_all_clients(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");

        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        Client::factory()->create(['user_id' => $ownerA->id]);
        Client::factory()->create(['user_id' => $ownerB->id]);

        $this->getJson('/api/clients')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_create_client(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/clients', [
            'name'  => 'Acme Corp',
            'email' => 'acme@example.com',
        ])->assertStatus(201)
          ->assertJsonPath('data.name', 'Acme Corp');
    }

    public function test_cannot_access_another_users_client(): void
    {
        $this->actingAsUser();
        $other  = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $other->id]);

        $this->getJson("/api/clients/{$client->id}")
             ->assertStatus(404);
    }

    public function test_can_update_own_client(): void
    {
        $user   = $this->actingAsUser();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $this->putJson("/api/clients/{$client->id}", ['name' => 'Updated Name'])
             ->assertStatus(200)
             ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_delete_own_client(): void
    {
        $user   = $this->actingAsUser();
        $client = Client::factory()->create(['user_id' => $user->id]);

        $this->deleteJson("/api/clients/{$client->id}")
             ->assertStatus(200);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }
}
