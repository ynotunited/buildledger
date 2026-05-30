<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_owner_can_issue_public_payment_link_for_sent_invoice(): void
    {
        $invoice = $this->createInvoice(status: 'Draft');

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/invoices/{$invoice->id}", [
            'status' => 'Sent',
        ]);

        $response->assertOk();

        $path = data_get($response->json(), 'data.public_payment_path');
        $this->assertIsString($path);
        $this->assertStringStartsWith('/pay/', $path);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Sent',
        ]);

        $invoice->refresh();
        $this->assertNotNull($invoice->public_payment_token);
    }

    public function test_public_invoice_can_be_loaded(): void
    {
        $invoice = $this->createInvoice(status: 'Sent');

        $this->getJson("/api/public/invoices/{$invoice->public_payment_token}")
            ->assertOk()
            ->assertJsonPath('invoice_number', $invoice->invoice_number);
    }

    public function test_public_payment_can_be_initiated_and_verified(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                ],
            ]),
            'api.paystack.co/transaction/verify/*' => Http::response([
                'data' => [
                    'status' => 'success',
                    'id' => 9999,
                ],
            ]),
        ]);

        $invoice = $this->createInvoice(status: 'Sent');

        $init = $this->postJson("/api/public/invoices/{$invoice->public_payment_token}/pay", [
            'gateway' => 'paystack',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $init->assertOk()
            ->assertJsonPath('authorization_url', 'https://paystack.test/auth');

        $paymentReference = $init->json('reference');

        $this->postJson("/api/public/invoices/{$invoice->public_payment_token}/verify", [
            'reference' => $paymentReference,
            'gateway' => 'paystack',
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $payment = Payment::query()->where('gateway_reference', $paymentReference)->firstOrFail();
        $this->assertSame('Completed', $payment->status);

        $this->assertDatabaseHas('payment_ledger_entries', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'event_type' => 'captured',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Paid',
        ]);
    }

    public function test_duplicate_public_payment_initiation_reuses_the_same_idempotency_key(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://paystack.test/auth',
                ],
            ]),
        ]);

        $invoice = $this->createInvoice(status: 'Sent');
        $idempotencyKey = (string) Str::uuid();

        $first = $this->postJson("/api/public/invoices/{$invoice->public_payment_token}/pay", [
            'gateway' => 'paystack',
            'idempotency_key' => $idempotencyKey,
        ]);

        $second = $this->postJson("/api/public/invoices/{$invoice->public_payment_token}/pay", [
            'gateway' => 'paystack',
            'idempotency_key' => $idempotencyKey,
        ]);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('reference'), $second->json('reference'));

        $payment = Payment::query()->where('gateway_reference', $first->json('reference'))->firstOrFail();
        $this->assertDatabaseCount('payment_ledger_entries', 2);
        $this->assertDatabaseHas('payment_ledger_entries', [
            'payment_id' => $payment->id,
            'event_type' => 'intent_created',
        ]);
    }

    private function createInvoice(string $status): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => $status,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Website design',
            'description' => 'Landing page redesign',
            'quantity' => 1,
            'unit_price' => 50000,
            'total' => 50000,
        ]);

        if ($status === 'Sent') {
            $invoice->forceFill([
                'sent_at' => now(),
                'public_payment_token' => (string) \Illuminate\Support\Str::uuid(),
                'public_payment_token_expires_at' => now()->addHours(168),
            ])->save();
        }

        return $invoice->fresh(['client', 'items']);
    }
}
