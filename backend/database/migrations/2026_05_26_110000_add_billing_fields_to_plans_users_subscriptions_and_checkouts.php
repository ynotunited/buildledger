<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('email_verified_at');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('price_annually_ngn')->nullable()->after('price_ngn');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('billing_interval', 32)->default('monthly')->after('gateway_transaction_id');
        });

        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->string('billing_interval', 32)->default('monthly')->after('amount_ngn');
        });

        DB::table('plans')->where('code', 'starter')->update([
            'price_annually_ngn' => 0,
            'updated_at' => now(),
        ]);

        DB::table('plans')->where('code', 'growth')->update([
            'price_annually_ngn' => 240000,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('billing_checkouts', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('billing_interval');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('price_annually_ngn');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('trial_ends_at');
        });
    }
};
