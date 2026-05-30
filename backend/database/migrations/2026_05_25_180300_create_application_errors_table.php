<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32)->default('backend');
            $table->string('level', 32)->default('error');
            $table->string('message');
            $table->string('exception_class')->nullable();
            $table->string('path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('request_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            $table->index(['source', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_errors');
    }
};
