<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('proposal_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->longText('body_content')->nullable();
            $table->string('status')->default('Draft'); // Draft, Sent, Signed
            $table->uuid('signing_token')->unique()->nullable();
            $table->string('client_signature_name')->nullable();
            $table->ipAddress('client_signature_ip')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
