<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('signing_token_expires_at')->nullable()->after('signing_token');
        });

        $expiry = now()->addHours((int) config('security.contract_signing_link_ttl_hours', 168));

        DB::table('contracts')
            ->where('status', 'Sent')
            ->whereNull('signing_token')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($contracts): void {
                foreach ($contracts as $contract) {
                    DB::table('contracts')
                        ->where('id', $contract->id)
                        ->update([
                            'signing_token' => (string) Str::uuid(),
                        ]);
                }
            });

        DB::table('contracts')
            ->where('status', 'Sent')
            ->whereNull('sent_at')
            ->update([
                'sent_at' => now(),
            ]);

        DB::table('contracts')
            ->where('status', 'Sent')
            ->whereNull('signing_token_expires_at')
            ->update([
                'signing_token_expires_at' => $expiry,
            ]);

        DB::table('contracts')
            ->where('status', '!=', 'Sent')
            ->update([
                'signing_token' => null,
                'signing_token_expires_at' => null,
                'sent_at' => DB::raw("CASE WHEN status = 'Signed' THEN client_signed_at ELSE NULL END"),
            ]);
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'signing_token_expires_at']);
        });
    }
};
