<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $savePlan = function (string $code, array $attributes) use ($now): void {
            $payload = array_merge($attributes, [
                'updated_at' => $now,
            ]);

            if (DB::table('plans')->where('code', $code)->exists()) {
                DB::table('plans')->where('code', $code)->update($payload);

                return;
            }

            DB::table('plans')->insert(array_merge([
                'code' => $code,
                'created_at' => $now,
            ], $payload));
        };

        $savePlan('starter', [
            'name' => 'Invite-only trial',
            'description' => 'Approved invites unlock a 30-day trial so teams can feel the workflow before they subscribe.',
            'price_ngn' => 0,
            'price_annually_ngn' => 0,
            'billing_interval' => 'monthly',
            'features' => json_encode(['trial_access', 'core_workflows', 'invite_only_onboarding']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $savePlan('starter_pro', [
            'name' => 'Starter',
            'description' => 'Stay on top of proposals, contracts, invoices, and follow-ups without juggling five tools.',
            'price_ngn' => 10000,
            'price_annually_ngn' => 96000,
            'billing_interval' => 'monthly',
            'features' => json_encode(['client_management', 'proposal_workflows', 'contract_signing', 'invoice_tracking']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $savePlan('growth', [
            'name' => 'Growth',
            'description' => 'See what is profitable, keep work moving, and manage client chaos from one place.',
            'price_ngn' => 25000,
            'price_annually_ngn' => 240000,
            'billing_interval' => 'monthly',
            'features' => json_encode(['client_management', 'proposal_workflows', 'invoice_tracking', 'analytics', 'priority_support']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $savePlan('agency', [
            'name' => 'Agency',
            'description' => 'Keep more clients, more approvals, and more moving parts under control without losing speed.',
            'price_ngn' => 50000,
            'price_annually_ngn' => 480000,
            'billing_interval' => 'monthly',
            'features' => json_encode(['client_management', 'proposal_workflows', 'invoice_tracking', 'analytics', 'priority_support', 'governance']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    public function down(): void
    {
        DB::table('plans')->where('code', 'agency')->delete();
        DB::table('plans')->where('code', 'starter_pro')->delete();

        DB::table('plans')->where('code', 'starter')->update([
            'name' => 'Starter',
            'description' => 'Core workspace for one brand with basic operations and client management.',
            'price_ngn' => 0,
            'price_annually_ngn' => 0,
            'billing_interval' => 'monthly',
            'features' => json_encode(['core_dashboard', 'issues', 'payments']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 1,
            'updated_at' => now(),
        ]);

        DB::table('plans')->where('code', 'growth')->update([
            'name' => 'Growth',
            'description' => 'Unlock analytics, enhanced monitoring, and premium support tooling.',
            'price_ngn' => 25000,
            'price_annually_ngn' => 240000,
            'billing_interval' => 'monthly',
            'features' => json_encode(['core_dashboard', 'issues', 'payments', 'analytics', 'priority_support']),
            'company_limit' => 1,
            'is_active' => true,
            'sort_order' => 2,
            'updated_at' => now(),
        ]);
    }
};
