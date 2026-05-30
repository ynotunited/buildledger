<?php

use App\Support\RowLevelSecurity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! RowLevelSecurity::supported()) {
            return;
        }

        foreach ($this->tablePolicies() as $table => $definition) {
            $this->applyPolicy($table, $definition['clause'], $definition['with_check'] ?? null);
        }
    }

    public function down(): void
    {
        if (! RowLevelSecurity::supported()) {
            return;
        }

        foreach (array_keys($this->tablePolicies()) as $table) {
            $this->dropPolicy($table);
        }
    }

    private function tablePolicies(): array
    {
        return [
            'clients' => ['clause' => $this->ownedByUserOrPublicDocumentClause('clients', 'id', 'client_id')],
            'companies' => ['clause' => $this->ownedByUserOrPublicDocumentClause('companies', 'id', 'company_id')],
            'proposals' => ['clause' => $this->ownedByUserOrPublicContractClause()],
            'proposal_items' => ['clause' => RowLevelSecurity::parentPolicy('proposals', 'proposal_id')],
            'contracts' => ['clause' => RowLevelSecurity::userOrPublicTokenPolicy('user_id', 'signing_token')],
            'invoices' => ['clause' => RowLevelSecurity::userOrPublicTokenPolicy('user_id', 'public_payment_token')],
            'invoice_items' => ['clause' => $this->ownedByUserOrPublicInvoiceClause('invoice_items', 'invoice_id')],
            'projects' => ['clause' => RowLevelSecurity::userPolicy()],
            'tasks' => ['clause' => RowLevelSecurity::parentPolicy('projects', 'project_id')],
            'payments' => ['clause' => $this->ownedByUserOrPublicInvoiceClause('payments', 'invoice_id')],
            'project_files' => ['clause' => RowLevelSecurity::userPolicy()],
            'issues' => ['clause' => RowLevelSecurity::userPolicy()],
            'subscriptions' => ['clause' => RowLevelSecurity::userPolicy()],
            'billing_checkouts' => ['clause' => RowLevelSecurity::userPolicy()],
            'idempotency_records' => ['clause' => $this->ownedByUserOrPublicIdempotencyClause()],
            'payment_ledger_entries' => ['clause' => $this->ownedByUserOrPublicInvoiceClause('payment_ledger_entries', 'invoice_id')],
            'security_incidents' => ['clause' => $this->ownedByUserOrPublicLogClause()],
            'analytics_events' => ['clause' => $this->ownedByUserOrPublicLogClause()],
            'application_errors' => ['clause' => $this->ownedByUserOrPublicLogClause()],
            'impersonation_events' => ['clause' => "current_setting('app.user_role', true) = 'admin'"],
            'operational_events' => ['clause' => "current_setting('app.user_role', true) = 'admin'"],
        ];
    }

    private function applyPolicy(string $table, string $clause, ?string $withCheck = null): void
    {
        $tableName = $this->quoteIdentifier($table);
        $policyName = $this->quoteIdentifier('rls_'.$table.'_access');
        $withCheck ??= $clause;

        DB::statement("ALTER TABLE {$tableName} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$tableName} FORCE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$tableName}");
        DB::statement(
            "CREATE POLICY {$policyName} ON {$tableName} FOR ALL USING ({$clause}) WITH CHECK ({$withCheck})"
        );
    }

    private function dropPolicy(string $table): void
    {
        $tableName = $this->quoteIdentifier($table);
        $policyName = $this->quoteIdentifier('rls_'.$table.'_access');

        DB::statement("DROP POLICY IF EXISTS {$policyName} ON {$tableName}");
        DB::statement("ALTER TABLE {$tableName} NO FORCE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$tableName} DISABLE ROW LEVEL SECURITY");
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function ownedByUserOrPublicDocumentClause(string $table, string $primaryKeyColumn, string $relationColumn): string
    {
        $table = $this->quoteIdentifier($table);
        $primaryKeyColumn = $this->quoteIdentifier($primaryKeyColumn);
        $relationColumn = $this->quoteIdentifier($relationColumn);
        $publicToken = "nullif(current_setting('app.public_access_token', true), '')::uuid";

        return sprintf(
            "%s OR (current_setting('app.access_mode', true) = 'public' AND (EXISTS (SELECT 1 FROM contracts WHERE contracts.%s = %s.%s AND contracts.signing_token = %s) OR EXISTS (SELECT 1 FROM invoices WHERE invoices.%s = %s.%s AND invoices.public_payment_token = %s)))",
            RowLevelSecurity::userPolicy("{$table}.user_id"),
            $relationColumn,
            $table,
            $primaryKeyColumn,
            $publicToken,
            $relationColumn,
            $table,
            $primaryKeyColumn,
            $publicToken
        );
    }

    private function ownedByUserOrPublicInvoiceClause(string $table, string $invoiceForeignKeyColumn): string
    {
        $table = $this->quoteIdentifier($table);
        $invoiceForeignKeyColumn = $this->quoteIdentifier($invoiceForeignKeyColumn);
        $publicToken = "nullif(current_setting('app.public_access_token', true), '')::uuid";

        return sprintf(
            "%s OR (current_setting('app.access_mode', true) = 'public' AND EXISTS (SELECT 1 FROM invoices WHERE invoices.id = %s.%s AND invoices.public_payment_token = %s))",
            RowLevelSecurity::userPolicy("{$table}.user_id"),
            $table,
            $invoiceForeignKeyColumn,
            $publicToken
        );
    }

    private function ownedByUserOrPublicIdempotencyClause(): string
    {
        $publicToken = "current_setting('app.public_access_token', true)";

        return sprintf(
            "%s OR (current_setting('app.access_mode', true) = 'public' AND metadata ->> 'token' = %s)",
            RowLevelSecurity::userPolicy(),
            $publicToken
        );
    }

    private function ownedByUserOrPublicContractClause(): string
    {
        $publicToken = "nullif(current_setting('app.public_access_token', true), '')::uuid";

        return sprintf(
            "%s OR (current_setting('app.access_mode', true) = 'public' AND EXISTS (SELECT 1 FROM contracts WHERE contracts.proposal_id = proposals.id AND contracts.signing_token = %s))",
            RowLevelSecurity::userPolicy('proposals.user_id'),
            $publicToken
        );
    }

    private function ownedByUserOrPublicLogClause(): string
    {
        return sprintf(
            "%s OR current_setting('app.access_mode', true) = 'public'",
            RowLevelSecurity::userPolicy()
        );
    }
};
