<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_ngn')->default(0);
            $table->string('billing_interval', 32)->default('monthly');
            $table->json('features')->nullable();
            $table->unsignedInteger('company_limit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default('active');
            $table->string('gateway', 32)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('billing_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 32);
            $table->string('reference')->unique();
            $table->unsignedInteger('amount_ngn');
            $table->string('status', 32)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        DB::table('plans')->insert([
            [
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Core workspace for one brand with basic operations and client management.',
                'price_ngn' => 0,
                'billing_interval' => 'monthly',
                'features' => json_encode(['core_dashboard', 'issues', 'payments']),
                'company_limit' => 1,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'growth',
                'name' => 'Growth',
                'description' => 'Unlock analytics, enhanced monitoring, and premium support tooling.',
                'price_ngn' => 25000,
                'billing_interval' => 'monthly',
                'features' => json_encode(['core_dashboard', 'issues', 'payments', 'analytics', 'priority_support']),
                'company_limit' => 1,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_checkouts');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
