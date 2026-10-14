<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_first_registered_user_becomes_an_admin(): void
    {
        $payload = [
            'name' => 'Ana Equipo',
            'email' => 'ana@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $firstUser = $this->postJson('/api/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('user.role', 'admin');
        $this->assertDatabaseHas('projects', [
            'owner_id' => $firstUser->json('user.id'),
            'name' => 'Mi primer proyecto',
        ]);
        $payload['email'] = 'bruno@example.test';
        $this->postJson('/api/v1/auth/register', $payload)->assertCreated()->assertJsonPath('user.role', 'member');
    }

    public function test_a_member_cannot_edit_another_members_task(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $project = Project::create(['owner_id' => $admin->id, 'name' => 'Atlas', 'key' => 'ATLAS']);
        $project->members()->attach($admin->id, ['role' => 'admin']);
        $project->members()->attach($member->id, ['role' => 'member']);
        $task = Task::create([
            'project_id' => $project->id,
            'assignee_id' => $admin->id,
            'created_by' => $admin->id,
            'title' => 'Revisar entrega',
        ]);

        Sanctum::actingAs($member);

        $this->patchJson("/api/v1/tasks/{$task->id}", ['status' => 'done'])->assertForbidden();
    }

    public function test_users_outside_a_project_cannot_read_its_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $outsider = User::factory()->create(['role' => 'member']);
        $project = Project::create(['owner_id' => $admin->id, 'name' => 'Atlas', 'key' => 'ATLAS']);
        $project->members()->attach($admin->id, ['role' => 'admin']);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/v1/projects/{$project->id}/tasks")->assertNotFound();
    }

    public function test_project_members_can_create_tasks_and_comments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);
        $project = Project::create(['owner_id' => $admin->id, 'name' => 'Atlas', 'key' => 'ATLAS']);
        $project->members()->attach($admin->id, ['role' => 'admin']);
        $project->members()->attach($member->id, ['role' => 'member']);

        Sanctum::actingAs($admin);

        $task = $this->postJson("/api/v1/projects/{$project->id}/tasks", [
            'title' => 'Preparar entrega',
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $member->id,
            'due_at' => '2026-10-16',
        ])
            ->assertCreated()
            ->assertJsonPath('data.assignee.id', $member->id)
            ->json('data.id');

        $this->actingAs($member)->postJson("/api/v1/tasks/{$task}/comments", [
            'body' => 'La primera versión está lista para revisar.',
        ])->assertCreated()->assertJsonPath('data.body', 'La primera versión está lista para revisar.');

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('tasks_by_status.in_progress', 1)
            ->assertJsonPath('workload.0.assignee.id', $member->id);
    }

    public function test_project_admin_can_add_an_existing_user_by_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['email' => 'member@example.test', 'role' => 'member']);
        $project = Project::create(['owner_id' => $admin->id, 'name' => 'Atlas', 'key' => 'ATLAS']);
        $project->members()->attach($admin->id, ['role' => 'admin']);

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/projects/{$project->id}/members", [
            'email' => $member->email,
            'role' => 'member',
        ])->assertCreated()->assertJsonPath('data.email', $member->email);

        $this->assertDatabaseHas('project_user', [
            'project_id' => $project->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);
    }
}