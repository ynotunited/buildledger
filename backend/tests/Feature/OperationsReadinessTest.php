<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\OperationalEvent;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\User;
use App\Support\DatabaseBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_creates_encrypted_snapshot_and_records_event(): void
    {
        Storage::fake('local');

        Artisan::call('ops:backup');

        $files = Storage::disk('local')->allFiles('backups');

        $this->assertNotEmpty($files);
        $encrypted = Storage::disk('local')->get($files[0]);
        $this->assertStringNotContainsString('"tables"', $encrypted);
        $this->assertArrayHasKey('tables', json_decode(Crypt::decryptString($encrypted), true));
        $this->assertDatabaseHas('operational_events', [
            'category' => 'backup',
            'severity' => 'success',
            'source' => 'ops:backup',
        ]);
    }

    public function test_backup_prune_removes_expired_snapshots(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'backups/buildledger-db-20260101_020000.json.enc',
            Crypt::encryptString('{"meta":{"created_at":"2026-01-01T02:00:00+00:00"},"tables":{}}')
        );
        Storage::disk('local')->put(
            'backups/buildledger-db-20260603_020000.json.enc',
            Crypt::encryptString('{"meta":{"created_at":"2026-06-03T02:00:00+00:00"},"tables":{}}')
        );

        $deleted = app(DatabaseBackupManager::class)->prune(30);

        $this->assertContains('backups/buildledger-db-20260101_020000.json.enc', $deleted);
        $this->assertTrue(Storage::disk('local')->exists('backups/buildledger-db-20260603_020000.json.enc'));
    }

    public function test_payment_reconciliation_heals_paid_invoice_from_ledger(): void
    {
        $owner = User::factory()->create();
        $client = Client::factory()->for($owner)->create();
        $invoice = Invoice::factory()->for($owner)->for($client)->create([
            'status' => 'Sent',
            'total' => 25000,
            'subtotal' => 25000,
        ]);

        $payment = Payment::create([
            'user_id' => $owner->id,
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'amount' => 25000,
            'currency' => 'NGN',
            'status' => 'Pending',
            'gateway' => 'Paystack',
            'gateway_reference' => 'REF-OPS-123',
            'notes' => 'Reconciliation test payment.',
        ]);

        PaymentLedgerEntry::create([
            'user_id' => $owner->id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway' => 'Paystack',
            'event_type' => 'captured',
            'gateway_event_id' => 'paystack:REF-OPS-123:captured',
            'gateway_reference' => 'REF-OPS-123',
            'dedupe_key' => 'paystack:REF-OPS-123:captured',
            'amount' => 25000,
            'currency' => 'NGN',
            'payload' => [
                'source' => 'test',
            ],
            'occurred_at' => now(),
        ]);

        Artisan::call('ops:reconcile-payments');

        $this->assertSame('Paid', $invoice->fresh()->status);
        $this->assertDatabaseHas('operational_events', [
            'category' => 'reconciliation',
            'severity' => 'success',
            'source' => 'ops:reconcile-payments',
        ]);
    }

    public function test_payments_kill_switch_blocks_checkout(): void
    {
        Config::set('ops.payments_enabled', false);

        $user = User::factory()->create();
        $token = $user->createToken('ops')->plainTextToken;
        $planCode = 'growth';

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/billing/checkout', [
                'plan_code' => $planCode,
                'gateway' => 'paystack',
                'billing_interval' => 'monthly',
            ])
            ->assertStatus(503);

        Config::set('ops.payments_enabled', true);
    }
}
