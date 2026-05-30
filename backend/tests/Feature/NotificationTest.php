<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\ContractSigned;
use App\Notifications\InvoicePaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $token       = $this->user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_can_list_notifications(): void
    {
        $this->user->notify(new InvoicePaid(
            Invoice::factory()->create([
                'user_id'   => $this->user->id,
                'client_id' => Client::factory()->create(['user_id' => $this->user->id])->id,
            ])
        ));

        $this->getJson('/api/notifications')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'current_page']);
    }

    public function test_can_get_unread_count(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => Client::factory()->create(['user_id' => $this->user->id])->id,
        ]);

        $this->user->notify(new InvoicePaid($invoice));

        $this->getJson('/api/notifications/unread')
             ->assertStatus(200)
             ->assertJsonPath('count', 1);
    }

    public function test_can_mark_all_notifications_read(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => Client::factory()->create(['user_id' => $this->user->id])->id,
        ]);

        $this->user->notify(new InvoicePaid($invoice));

        $this->postJson('/api/notifications/mark-all-read')
             ->assertStatus(200);

        $this->getJson('/api/notifications/unread')
             ->assertJsonPath('count', 0);
    }

    public function test_contract_signed_fires_notification(): void
    {
        Notification::fake();

        $client   = Client::factory()->create(['user_id' => $this->user->id]);
        $contract = Contract::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
            'status'    => 'Sent',
        ]);

        $this->postJson("/api/public/contracts/{$contract->signing_token}/sign", [
            'signature_name' => 'John Client',
        ])->assertStatus(200);

        Notification::assertSentTo($this->user, ContractSigned::class);
    }

    public function test_public_contract_cannot_be_signed_while_in_draft(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => 'Draft',
            'signing_token' => null,
            'signing_token_expires_at' => null,
        ]);

        $this->postJson("/api/public/contracts/{$contract->id}/sign", [
            'signature_name' => 'John Client',
        ])->assertStatus(404);
    }

    public function test_public_contract_link_expires(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $contract = Contract::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => 'Sent',
            'signing_token_expires_at' => now()->subMinute(),
        ]);

        $this->getJson("/api/public/contracts/{$contract->signing_token}")
            ->assertStatus(410);
    }
}
