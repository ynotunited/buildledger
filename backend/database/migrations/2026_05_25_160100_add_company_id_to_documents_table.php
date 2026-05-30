<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
