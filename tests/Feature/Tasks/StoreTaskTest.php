<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StoreTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_redirects_guests_and_renders_the_authenticated_task_workspace(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user, 'owner')->create(['name' => 'Personal']);

        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Create a new task')
            ->assertSee('Delete task?')
            ->assertSee('Add Task Successfully')
            ->assertSee('data-modal-open="create-task-modal"', false)
            ->assertSee('id="task-list"', false)
            ->assertSee('No tasks yet.');
    }

    public function test_valid_task_input_persists_the_task_and_renders_escaped_task_data(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Lina Kim']);
        $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Product Design']);
        $title = '<script>alert("task")</script>';

        $response = $this->actingAs($owner)->postJson(route('tasks.store'), [
            'title' => $title,
            'description' => 'Prepare the release checklist.',
            'project' => $project->getKey(),
            'priority' => 'High',
            'status' => 'Completed',
            'due_date' => today()->toDateString(),
            'assignees' => [$assignee->getKey()],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Task created')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($title)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $title))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Today'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'LK'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Mark incomplete'));

        $task = Task::query()->where('title', $title)->firstOrFail();
        $this->assertModelExists($task);
        $this->assertDatabaseHas('task_assignees', [
            'task_id' => $task->getKey(),
            'user_id' => $assignee->getKey(),
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'user_id' => $owner->getKey(),
            'event_type' => 'created',
            'to_status' => TaskStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $assignee->getKey(),
            'notifiable_type' => User::class,
        ]);
    }

    public function test_task_creation_rejects_missing_required_fields_and_unknown_assignees(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->postJson(route('tasks.store'), [
                'project' => $project->getKey(),
                'assignees' => [999999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'priority', 'status', 'due_date', 'assignees.0']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_task_creation_cannot_target_another_users_project(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        Project::factory()->for($owner, 'owner')->create();
        $project = Project::factory()->for($otherOwner, 'owner')->create();

        $this->actingAs($owner)
            ->postJson(route('tasks.store'), [
                'title' => 'Do not cross project boundaries',
                'project' => $project->getKey(),
                'priority' => 'Medium',
                'status' => 'Open',
                'due_date' => today()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('project');

        $this->assertDatabaseCount('tasks', 0);
    }
}
