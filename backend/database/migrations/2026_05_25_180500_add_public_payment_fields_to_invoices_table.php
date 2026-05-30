<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->uuid('public_payment_token')->nullable()->unique()->after('sent_at');
            $table->timestamp('public_payment_token_expires_at')->nullable()->after('public_payment_token');
        });

        $expiry = Carbon::now()->addHours((int) config('security.invoice_payment_link_ttl_hours', 168));

        DB::table('invoices')
            ->where('status', 'Sent')
            ->whereNull('public_payment_token')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use ($expiry): void {
                foreach ($invoices as $invoice) {
                    DB::table('invoices')
                        ->where('id', $invoice->id)
                        ->update([
                            'sent_at' => $invoice->sent_at ?? now(),
                            'public_payment_token' => (string) Str::uuid(),
                            'public_payment_token_expires_at' => $expiry,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'public_payment_token_expires_at', 'public_payment_token']);
        });
    }
};
