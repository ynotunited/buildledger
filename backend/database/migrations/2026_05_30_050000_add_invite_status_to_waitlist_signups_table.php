<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waitlist_signups', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('source');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('approved_at');
            $table->timestamp('activated_at')->nullable()->after('approved_by_user_id');
            $table->timestamp('rejected_at')->nullable()->after('activated_at');

            $table->index('status');
            $table->index('approved_at');
        });

    }

    public function down(): void
    {
        Schema::table('waitlist_signups', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_at']);
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn([
                'status',
                'approved_at',
                'activated_at',
                'rejected_at',
            ]);
        });
    }
};
