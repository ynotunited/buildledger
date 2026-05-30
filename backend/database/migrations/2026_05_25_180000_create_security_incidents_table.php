<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 100);
            $table->string('severity', 32)->default('warning');
            $table->string('ip_address', 45)->nullable();
            $table->string('path')->nullable();
            $table->string('method', 12)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('identity_key')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
            $table->index(['ip_address', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_incidents');
    }
};
