<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('owner')->after('email_verified_at');
            $table->string('google_id')->nullable()->unique()->after('role');
            $table->string('email_verification_token', 64)->nullable()->after('remember_token');
            $table->timestamp('email_verification_sent_at')->nullable()->after('email_verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'google_id',
                'email_verification_token',
                'email_verification_sent_at',
            ]);
        });
    }
};
