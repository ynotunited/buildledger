<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->applyIndexes(true);
    }

    public function down(): void
    {
        $this->applyIndexes(false);
    }

    private function applyIndexes(bool $create): void
    {
        foreach ($this->indexMap() as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($indexes, $create): void {
                foreach ($indexes as $index) {
                    if ($create) {
                        $table->index($index['columns'], $index['name']);
                    } else {
                        $table->dropIndex($index['name']);
                    }
                }
            });
        }
    }

    /**
     * @return array<string, array<int, array{columns: array<int, string>, name: string}>>
     */
    private function indexMap(): array
    {
        return [
            'clients' => [
                ['columns' => ['user_id', 'status'], 'name' => 'clients_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'clients_user_created_idx'],
            ],
            'proposals' => [
                ['columns' => ['user_id', 'status'], 'name' => 'proposals_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'proposals_user_created_idx'],
                ['columns' => ['client_id'], 'name' => 'proposals_client_idx'],
                ['columns' => ['company_id'], 'name' => 'proposals_company_idx'],
            ],
            'proposal_items' => [
                ['columns' => ['proposal_id'], 'name' => 'proposal_items_proposal_idx'],
            ],
            'contracts' => [
                ['columns' => ['user_id', 'status'], 'name' => 'contracts_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'contracts_user_created_idx'],
                ['columns' => ['client_id'], 'name' => 'contracts_client_idx'],
                ['columns' => ['proposal_id'], 'name' => 'contracts_proposal_idx'],
                ['columns' => ['company_id'], 'name' => 'contracts_company_idx'],
            ],
            'invoices' => [
                ['columns' => ['user_id', 'status'], 'name' => 'invoices_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'invoices_user_created_idx'],
                ['columns' => ['client_id'], 'name' => 'invoices_client_idx'],
                ['columns' => ['contract_id'], 'name' => 'invoices_contract_idx'],
                ['columns' => ['company_id'], 'name' => 'invoices_company_idx'],
            ],
            'invoice_items' => [
                ['columns' => ['invoice_id'], 'name' => 'invoice_items_invoice_idx'],
            ],
            'projects' => [
                ['columns' => ['user_id', 'status'], 'name' => 'projects_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'projects_user_created_idx'],
                ['columns' => ['client_id'], 'name' => 'projects_client_idx'],
                ['columns' => ['contract_id'], 'name' => 'projects_contract_idx'],
            ],
            'tasks' => [
                ['columns' => ['project_id', 'status'], 'name' => 'tasks_project_status_idx'],
                ['columns' => ['project_id', 'due_date'], 'name' => 'tasks_project_due_idx'],
            ],
            'payments' => [
                ['columns' => ['user_id', 'status'], 'name' => 'payments_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'payments_user_created_idx'],
                ['columns' => ['invoice_id'], 'name' => 'payments_invoice_idx'],
                ['columns' => ['client_id'], 'name' => 'payments_client_idx'],
            ],
            'project_files' => [
                ['columns' => ['user_id', 'project_id'], 'name' => 'project_files_user_project_idx'],
            ],
            'billing_checkouts' => [
                ['columns' => ['user_id', 'status'], 'name' => 'billing_checkouts_user_status_idx'],
                ['columns' => ['user_id', 'created_at'], 'name' => 'billing_checkouts_user_created_idx'],
                ['columns' => ['plan_id'], 'name' => 'billing_checkouts_plan_idx'],
            ],
            'idempotency_records' => [
                ['columns' => ['user_id', 'created_at'], 'name' => 'idempotency_records_user_created_idx'],
            ],
            'payment_ledger_entries' => [
                ['columns' => ['user_id', 'occurred_at'], 'name' => 'payment_ledger_entries_user_occurred_idx'],
                ['columns' => ['user_id', 'event_type'], 'name' => 'payment_ledger_entries_user_event_idx'],
            ],
            'security_incidents' => [
                ['columns' => ['user_id', 'occurred_at'], 'name' => 'security_incidents_user_occurred_idx'],
            ],
            'application_errors' => [
                ['columns' => ['user_id', 'occurred_at'], 'name' => 'application_errors_user_occurred_idx'],
            ],
            'operational_events' => [
                ['columns' => ['user_id', 'occurred_at'], 'name' => 'operational_events_user_occurred_idx'],
            ],
            'impersonation_events' => [
                ['columns' => ['impersonator_user_id', 'created_at'], 'name' => 'impersonation_events_impersonator_created_idx'],
                ['columns' => ['target_user_id', 'created_at'], 'name' => 'impersonation_events_target_created_idx'],
            ],
        ];
    }
};
