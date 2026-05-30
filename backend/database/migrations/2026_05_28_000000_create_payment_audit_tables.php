<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scope', 80);
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->string('status', 32)->default('processing');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('resource_type', 120)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'idempotency_key']);
            $table->index(['resource_type', 'resource_id']);
        });

        Schema::create('payment_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_checkout_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 32)->nullable();
            $table->string('event_type', 64);
            $table->string('gateway_event_id', 191)->nullable();
            $table->string('gateway_reference', 191)->nullable();
            $table->string('dedupe_key', 191)->unique();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 10)->default('NGN');
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->unique('gateway_event_id');
            $table->index(['payment_id', 'event_type']);
            $table->index(['invoice_id', 'event_type']);
            $table->index(['billing_checkout_id', 'event_type']);
            $table->index(['subscription_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_ledger_entries');
        Schema::dropIfExists('idempotency_records');
    }
};
