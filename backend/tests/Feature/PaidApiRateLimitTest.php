<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaidApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_invoice_payment_hits_paid_api_rate_limit(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/authorize',
                ],
            ], 200),
        ]);

        $owner = User::factory()->create();
        $client = Client::factory()->for($owner)->create([
            'email' => 'client@example.com',
        ]);
        $invoice = Invoice::factory()->for($owner)->for($client)->create([
            'status' => 'Sent',
            'total' => 15000,
            'subtotal' => 15000,
            'public_payment_token' => (string) Str::uuid(),
            'public_payment_token_expires_at' => now()->addDay(),
        ]);

        for ($index = 0; $index < 8; $index++) {
            $this->withHeader('Idempotency-Key', 'public-pay-'.$index)
                ->postJson("/api/public/invoices/{$invoice->public_payment_token}/pay", [
                    'gateway' => 'paystack',
                ])
                ->assertOk();
        }

        $this->withHeader('Idempotency-Key', 'public-pay-blocked')
            ->postJson("/api/public/invoices/{$invoice->public_payment_token}/pay", [
                'gateway' => 'paystack',
            ])
            ->assertStatus(429);
    }
}
