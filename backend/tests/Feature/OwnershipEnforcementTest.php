<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnershipEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_cannot_create_proposal_for_another_users_client(): void
    {
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);

        $this->postJson('/api/proposals', [
            'client_id' => $foreignClient->id,
            'title' => 'Leaked proposal',
            'issue_date' => '2026-05-01',
            'items' => [
                ['name' => 'Work', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_cannot_create_contract_with_another_users_proposal(): void
    {
        $ownedClient = Client::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignProposal = $other->proposals()->create([
            'client_id' => $foreignClient->id,
            'title' => 'Foreign proposal',
            'status' => 'Draft',
            'issue_date' => now(),
            'subtotal' => 1000,
            'tax' => 0,
            'total' => 1000,
        ]);

        $this->postJson('/api/contracts', [
            'client_id' => $ownedClient->id,
            'proposal_id' => $foreignProposal->id,
            'title' => 'Invalid contract',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['proposal_id']);
    }

    public function test_cannot_create_invoice_with_another_users_contract(): void
    {
        $ownedClient = Client::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignContract = Contract::factory()->create([
            'user_id' => $other->id,
            'client_id' => $foreignClient->id,
        ]);

        $this->postJson('/api/invoices', [
            'client_id' => $ownedClient->id,
            'contract_id' => $foreignContract->id,
            'issue_date' => '2026-05-01',
            'due_date' => '2026-05-10',
            'items' => [
                ['name' => 'Phase 1', 'quantity' => 1, 'unit_price' => 5000],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['contract_id']);
    }

    public function test_cannot_create_project_with_another_users_contract(): void
    {
        $ownedClient = Client::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignContract = Contract::factory()->create([
            'user_id' => $other->id,
            'client_id' => $foreignClient->id,
        ]);

        $this->postJson('/api/projects', [
            'client_id' => $ownedClient->id,
            'contract_id' => $foreignContract->id,
            'title' => 'Compromised project',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['contract_id']);
    }

    public function test_cannot_record_payment_for_another_users_invoice(): void
    {
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignInvoice = Invoice::factory()->create([
            'user_id' => $other->id,
            'client_id' => $foreignClient->id,
        ]);

        $this->postJson('/api/payments/manual', [
            'invoice_id' => $foreignInvoice->id,
            'amount' => 1000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_id']);
    }

    public function test_cannot_attach_files_to_another_users_project(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignProject = Project::factory()->create([
            'user_id' => $other->id,
            'client_id' => $foreignClient->id,
        ]);

        $this->post('/api/files', [
            'project_id' => $foreignProject->id,
            'files' => [UploadedFile::fake()->image('evidence.png')],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    public function test_cannot_reorder_tasks_from_another_project(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);
        $otherProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);

        $foreignTask = Task::query()->create([
            'project_id' => $otherProject->id,
            'title' => 'Foreign task',
            'status' => 'Todo',
            'priority' => 'Medium',
            'position' => 0,
        ]);

        $this->postJson("/api/projects/{$project->id}/tasks/reorder", [
            'tasks' => [
                [
                    'id' => $foreignTask->id,
                    'status' => 'Done',
                    'position' => 0,
                ],
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['tasks']);
    }

    public function test_cannot_fetch_another_users_payment_by_id(): void
    {
        $other = User::factory()->create();
        $foreignClient = Client::factory()->create(['user_id' => $other->id]);
        $foreignInvoice = Invoice::factory()->create([
            'user_id' => $other->id,
            'client_id' => $foreignClient->id,
        ]);
        $foreignPayment = Payment::query()->create([
            'user_id' => $other->id,
            'invoice_id' => $foreignInvoice->id,
            'client_id' => $foreignClient->id,
            'amount' => 1000,
            'currency' => 'NGN',
            'status' => 'Completed',
            'gateway' => 'Manual',
            'paid_at' => now(),
        ]);

        $this->getJson("/api/payments/{$foreignPayment->id}")
            ->assertStatus(404);
    }
}
