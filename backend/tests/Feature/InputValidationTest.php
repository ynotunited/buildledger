<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InputValidationTest extends TestCase
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

    public function test_client_input_is_sanitized_before_storage(): void
    {
        $response = $this->postJson('/api/clients', [
            'name' => '  <script>alert(1)</script> Acme Corp  ',
            'company' => '<b>Acme Holdings</b>',
            'address' => "<div>Line 1</div>\n<script>evil()</script>",
            'status' => 'Active',
        ])->assertStatus(201);

        $this->assertSame('Acme Corp', $response->json('data.name'));
        $this->assertSame('Acme Holdings', $response->json('data.company'));
        $this->assertSame('Line 1', $response->json('data.address'));
    }

    public function test_contract_body_content_is_sanitized_but_keeps_safe_markup(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/contracts', [
            'client_id' => $client->id,
            'title' => '  <b>Main Contract</b>  ',
            'body_content' => '<script>alert(1)</script><p onclick="evil()">Hello <strong>client</strong> <a href="javascript:alert(1)">click</a></p>',
        ])->assertStatus(201);

        $body = $response->json('data.body_content');

        $this->assertSame('Main Contract', $response->json('data.title'));
        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick=', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringContainsString('<strong>client</strong>', $body);
    }

    public function test_unsafe_file_upload_is_rejected(): void
    {
        Storage::fake('public');
        config(['filesystems.default' => 'public']);

        $this->post('/api/files', [
            'files' => [
                UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['files.0']);
    }

    public function test_invalid_task_status_is_rejected(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
        ]);

        $this->postJson("/api/projects/{$project->id}/tasks", [
            'title' => 'Review drawings',
            'status' => 'Dropped Table',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
