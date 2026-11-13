<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TaskProgressTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_stage_updates_complete_a_task_and_restore_its_previous_progress_when_reopened(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::InProgress,
            'progress' => 25,
        ]);
        $task->assignees()->attach($assignee);
        $this->actingAs($owner);

        $this->patchJson(route('tasks.progress', ['task' => $task]), [
            'progress' => 50,
            'expected_progress' => 25,
        ])->assertOk()
            ->assertJsonPath('task.progress', 50)
            ->assertJsonPath('task.progress_label', 'Halfway')
            ->assertJsonPath('task.status', TaskStatus::InProgress->value);

        $this->patchJson(route('tasks.progress', ['task' => $task]), [
            'progress' => 75,
            'expected_progress' => 50,
        ])->assertOk()
            ->assertJsonPath('task.progress', 75);

        $this->patchJson(route('tasks.progress', ['task' => $task]), [
            'progress' => 100,
            'expected_progress' => 75,
        ])->assertOk()
            ->assertJsonPath('task.progress', 100)
            ->assertJsonPath('task.status', TaskStatus::Completed->value);

        $this->patchJson(route('tasks.progress', ['task' => $task]), [
            'progress' => 50,
            'expected_progress' => 100,
        ])->assertOk()
            ->assertJsonPath('task.progress', 50)
            ->assertJsonPath('task.status', TaskStatus::InProgress->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'progress' => 50,
            'progress_before_complete' => null,
            'progress_updated_by_id' => $owner->getKey(),
            'status' => TaskStatus::InProgress->value,
            'completed_at' => null,
            'completed_by_id' => null,
        ]);
        $this->assertSame(5, TaskActivity::query()->where('task_id', $task->getKey())->count());
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'status_changed',
            'metadata->message' => 'reopened this task. Progress restored to 50%.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.progress_changed',
            'subject_id' => $task->getKey(),
        ]);
    }

    public function test_status_updates_and_task_creation_apply_progress_rules(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
            'progress' => 0,
        ]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.status.update', ['task' => $task]), [
                'status' => TaskStatus::Completed->value,
            ])
            ->assertOk()
            ->assertJsonPath('task.progress', 100)
            ->assertJsonPath('task.status', TaskStatus::Completed->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'progress_before_complete' => 0,
            'completed_by_id' => $owner->getKey(),
        ]);

        $this->patchJson(route('tasks.status.update', ['task' => $task]), [
            'status' => TaskStatus::InProgress->value,
        ])->assertOk()
            ->assertJsonPath('task.progress', 0)
            ->assertJsonPath('task.status', TaskStatus::Open->value);

        $createdResponse = $this->postJson(route('tasks.store'), [
            'title' => 'Start the new task',
            'project' => $project->getKey(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::InProgress->value,
            'due_date' => today()->toDateString(),
        ]);
        $createdTask = Task::query()->findOrFail($createdResponse->assertCreated()->json('task_id'));

        $this->assertSame(TaskStatus::InProgress, $createdTask->status);
        $this->assertSame(25, $createdTask->progress);

        $this->patchJson(route('tasks.update', ['task' => $createdTask]), [
            'title' => 'Start the new task',
            'description' => null,
            'project' => $project->getKey(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Completed->value,
            'due_date' => today()->toDateString(),
        ])->assertOk()
            ->assertJsonPath('task.progress', 100);

        $this->patchJson(route('tasks.update', ['task' => $createdTask]), [
            'title' => 'Start the new task',
            'description' => null,
            'project' => $project->getKey(),
            'priority' => TaskPriority::High->value,
            'status' => TaskStatus::Open->value,
            'due_date' => today()->toDateString(),
        ])->assertOk()
            ->assertJsonPath('task.progress', 25)
            ->assertJsonPath('task.status', TaskStatus::InProgress->value);

        $completedCreation = $this->postJson(route('tasks.store'), [
            'title' => 'Complete the new task',
            'project' => $project->getKey(),
            'priority' => TaskPriority::Medium->value,
            'status' => TaskStatus::Completed->value,
            'due_date' => today()->toDateString(),
        ]);
        $completedTask = Task::query()->findOrFail($completedCreation->assertCreated()->json('task_id'));

        $this->assertSame(TaskStatus::Completed, $completedTask->status);
        $this->assertSame(100, $completedTask->progress);
        $this->assertSame($owner->getKey(), $completedTask->completed_by_id);
    }

    public function test_progress_update_returns_409_with_the_latest_task_when_expected_progress_is_stale(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::InProgress,
            'progress' => 25,
        ]);

        $activityCount = TaskActivity::query()->where('task_id', $task->getKey())->count();

        $this->actingAs($owner)
            ->patchJson(route('tasks.progress', ['task' => $task]), [
                'progress' => 50,
                'expected_progress' => 75,
            ])
            ->assertConflict()
            ->assertJsonPath('task.progress', 25)
            ->assertJsonPath('task.status', TaskStatus::InProgress->value);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'progress' => 25,
        ]);
        $this->assertSame($activityCount, TaskActivity::query()->where('task_id', $task->getKey())->count());
    }

    public function test_returns_422_when_progress_is_not_an_allowed_stage(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'progress' => 0,
        ]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.progress', ['task' => $task]), [
                'progress' => 35,
                'expected_progress' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('progress')
            ->assertJsonPath('errors.progress.0', 'The selected progress is invalid.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'progress' => 0,
        ]);
    }

    public function test_progress_policy_allows_creators_assignees_and_managers_but_denies_owners_and_admins(): void
    {
        $projectOwner = User::factory()->create();
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->for($projectOwner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $creator->getKey(),
            'progress' => 0,
        ]);
        $task->assignees()->attach($assignee);

        $this->assertTrue(Gate::forUser($creator)->allows('updateProgress', $task));
        $this->assertTrue(Gate::forUser($assignee)->allows('updateProgress', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('updateProgress', $task));
        $this->assertFalse(Gate::forUser($projectOwner)->allows('updateProgress', $task));
        $this->assertFalse(Gate::forUser($admin)->allows('updateProgress', $task));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('updateProgress', $task));
    }

    public function test_progress_endpoint_returns_403_for_a_user_without_progress_permission(): void
    {
        $projectOwner = User::factory()->create();
        $creator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $creator->getKey(),
            'progress' => 0,
        ]);

        $this->actingAs($projectOwner)
            ->patchJson(route('tasks.progress', ['task' => $task]), [
                'progress' => 25,
                'expected_progress' => 0,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'progress' => 0,
        ]);
    }

    public function test_progress_endpoint_returns_401_for_a_guest(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'progress' => 0,
        ]);

        $this->patchJson(route('tasks.progress', ['task' => $task]), [
            'progress' => 25,
            'expected_progress' => 0,
        ])->assertUnauthorized();
    }

    public function test_project_details_render_for_visible_tasks_and_redirect_for_missing_or_hidden_tasks(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $privateProject = Project::factory()->for($otherOwner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'priority' => TaskPriority::High,
        ]);
        $hiddenTask = Task::factory()->for($privateProject)->create([
            'created_by_id' => $otherOwner->getKey(),
            'priority' => TaskPriority::High,
        ]);

        $this->actingAs($owner)
            ->get(route('projects.show', ['task' => $task]))
            ->assertOk()
            ->assertSee('id="project-modal-data"', false)
            ->assertSee('data-progress-stages', false)
            ->assertSee($task->title);

        $this->get(route('projects.show', ['task' => $hiddenTask]))
            ->assertRedirectToRoute('projects.index')
            ->assertSessionHas('status', "Project not found or you don't have access");

        $this->get(route('projects.show', ['task' => 999999]))
            ->assertRedirectToRoute('projects.index')
            ->assertSessionHas('status', "Project not found or you don't have access");
    }

    public function test_task_presenter_does_not_expose_email_addresses(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
        ]);
        $task->assignees()->attach($assignee);

        $this->actingAs($owner)
            ->getJson(route('tasks.show', ['task' => $task]))
            ->assertOk()
            ->assertJsonPath('task.progress', 0)
            ->assertJsonPath('task.people.0.role', 'owner')
            ->assertDontSee($owner->email)
            ->assertDontSee($assignee->email);
    }

    public function test_sync_progress_repairs_mismatched_tasks_and_is_idempotent(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
            'progress' => 0,
        ]);
        DB::table('tasks')->where('id', $task->getKey())->update(['progress' => 100]);

        $this->artisan('tasks:sync-progress')
            ->expectsOutput('Fixed 1 task records.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'status' => TaskStatus::Completed->value,
            'progress' => 100,
        ]);

        $this->artisan('tasks:sync-progress')
            ->expectsOutput('Fixed 0 task records.')
            ->assertExitCode(0);
    }

    public function test_projects_page_loads_task_details_without_per_task_queries(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        Task::factory()->count(5)->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'priority' => TaskPriority::High,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($owner)->get(route('projects.index'))->assertOk();

        $selectCount = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_starts_with(mb_strtolower(ltrim($query['query'])), 'select'))
            ->count();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(15, $selectCount);
    }
}
