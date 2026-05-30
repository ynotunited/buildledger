<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Notifications\InvoicePaymentLinkSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    public function test_can_create_invoice(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->postJson('/api/invoices', [
            'client_id'  => $client->id,
            'issue_date' => '2026-05-01',
            'due_date'   => '2026-05-15',
            'items'      => [
                ['name' => 'Design work', 'quantity' => 1, 'unit_price' => 50000],
            ],
        ])->assertStatus(201)
          ->assertJsonPath('data.status', 'Draft')
          ->assertJsonStructure(['data' => ['invoice_number', 'total', 'items']]);
    }

    public function test_invoice_number_is_sequential(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $payload = fn () => [
            'client_id'  => $client->id,
            'issue_date' => '2026-05-01',
            'due_date'   => '2026-05-15',
            'items'      => [['name' => 'Item', 'quantity' => 1, 'unit_price' => 1000]],
        ];

        $first  = $this->postJson('/api/invoices', $payload())->json('data.invoice_number');
        $second = $this->postJson('/api/invoices', $payload())->json('data.invoice_number');

        $this->assertNotEquals($first, $second);
        $this->assertStringStartsWith('INV-', $first);
    }

    public function test_cannot_access_another_users_invoice(): void
    {
        $other   = User::factory()->create();
        $client  = Client::factory()->create(['user_id' => $other->id]);
        $invoice = Invoice::factory()->create([
            'user_id'   => $other->id,
            'client_id' => $client->id,
        ]);

        $this->getJson("/api/invoices/{$invoice->id}")
             ->assertStatus(404);
    }

    public function test_can_update_invoice_status(): void
    {
        $client  = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
            'status'    => 'Draft',
        ]);

        $this->putJson("/api/invoices/{$invoice->id}", ['status' => 'Sent'])
             ->assertStatus(200)
             ->assertJsonPath('data.status', 'Sent');
    }

    public function test_can_send_payment_link_to_client_email(): void
    {
        Notification::fake();

        $client = Client::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'client@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
            'status'    => 'Draft',
        ]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/send-payment-link");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Sent')
            ->assertJsonStructure(['data' => ['public_payment_path']]);

        Notification::assertSentOnDemand(InvoicePaymentLinkSent::class, function ($notification, array $channels, $notifiable) use ($client) {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $client->email;
        });
    }

    public function test_can_list_invoices_paginated(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->count(5)->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
        ]);

        $this->getJson('/api/invoices')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'total']]);
    }
}
