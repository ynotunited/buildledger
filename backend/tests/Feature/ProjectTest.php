<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $token       = $this->user->createToken('test')->plainTextToken;
        $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_can_create_project(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->postJson('/api/projects', [
            'client_id' => $client->id,
            'title'     => 'Website Redesign',
            'status'    => 'Planning',
        ])->assertStatus(201)
          ->assertJsonPath('data.title', 'Website Redesign');
    }

    public function test_can_add_task_to_project(): void
    {
        $client  = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
        ]);

        $this->postJson("/api/projects/{$project->id}/tasks", [
            'title'    => 'Design mockups',
            'status'   => 'Todo',
            'priority' => 'High',
        ])->assertStatus(201)
          ->assertJsonPath('title', 'Design mockups');
    }

    public function test_can_move_task_between_columns(): void
    {
        $client  = Client::factory()->create(['user_id' => $this->user->id]);
        $project = Project::factory()->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
        ]);

        $task = $this->postJson("/api/projects/{$project->id}/tasks", [
            'title'  => 'Task A',
            'status' => 'Todo',
        ])->json();

        $this->putJson("/api/projects/{$project->id}/tasks/{$task['id']}", [
            'status' => 'In Progress',
        ])->assertStatus(200)
          ->assertJsonPath('status', 'In Progress');
    }

    public function test_cannot_access_another_users_project(): void
    {
        $other   = User::factory()->create();
        $client  = Client::factory()->create(['user_id' => $other->id]);
        $project = Project::factory()->create([
            'user_id'   => $other->id,
            'client_id' => $client->id,
        ]);

        $this->getJson("/api/projects/{$project->id}")
             ->assertStatus(404);
    }

    public function test_projects_are_paginated(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        Project::factory()->count(5)->create([
            'user_id'   => $this->user->id,
            'client_id' => $client->id,
        ]);

        $this->getJson('/api/projects')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page']]);
    }
}
