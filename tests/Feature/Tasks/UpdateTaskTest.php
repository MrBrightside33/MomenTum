<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_update_persists_task_changes_and_escapes_user_content(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Lina Kim']);
        $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Product Design']);
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Initial task title',
            'due_date' => today()->addDays(3),
        ]);
        $title = 'Review <script>alert("updated")</script>';
        $description = 'Replace unsafe content: <img src=x onerror=alert(1)>';

        $response = $this->actingAs($owner)->patchJson(route('tasks.update', ['task' => $task]), [
            'title' => $title,
            'description' => $description,
            'project' => $project->getKey(),
            'priority' => 'High',
            'status' => 'In Progress',
            'due_date' => '2020-05-01',
            'assignees' => [$assignee->getKey()],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Task updated')
            ->assertJsonPath('task_id', $task->getKey())
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($title)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $title))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($description)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $description))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'May 1'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'title' => $title,
            'description' => $description,
            'priority' => 'High',
            'status' => TaskStatus::InProgress->value,
            'due_date' => '2020-05-01 00:00:00',
        ]);
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->getKey(),
            'user_id' => $assignee->getKey(),
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'status_changed',
            'from_status' => TaskStatus::Open->value,
            'to_status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_update_rejects_invalid_fields_without_changing_the_task(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $otherProject = Project::factory()->for($otherOwner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Keep original title',
        ]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', ['task' => $task]), [
                'title' => 'ab',
                'project' => $otherProject->getKey(),
                'priority' => 'Urgent',
                'status' => 'Started',
                'due_date' => 'not-a-date',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'project', 'priority', 'status', 'due_date']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'title' => 'Keep original title',
            'project_id' => $project->getKey(),
        ]);
    }
}
