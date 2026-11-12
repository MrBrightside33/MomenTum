<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assignee_can_complete_a_task_and_the_owner_receives_a_notification(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
        ]);
        $task->assignees()->attach($assignee);

        $response = $this->actingAs($assignee)->patchJson(
            route('tasks.status.update', ['task' => $task]),
            ['status' => TaskStatus::Completed->value],
        );

        $response
            ->assertOk()
            ->assertJsonPath('status', TaskStatus::Completed->value)
            ->assertJsonPath('done', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'status' => TaskStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'user_id' => $assignee->getKey(),
            'event_type' => 'status_changed',
            'from_status' => TaskStatus::Open->value,
            'to_status' => TaskStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->getKey(),
            'notifiable_type' => User::class,
        ]);
    }

    public function test_repeating_a_status_update_does_not_create_duplicate_activity_or_notifications(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
        ]);
        $task->assignees()->attach($assignee);
        $this->actingAs($assignee);

        $this->patchJson(route('tasks.status.update', ['task' => $task]), [
            'status' => TaskStatus::Completed->value,
        ])->assertOk();
        $this->patchJson(route('tasks.status.update', ['task' => $task]), [
            'status' => TaskStatus::Completed->value,
        ])->assertOk();

        $this->assertSame(
            1,
            TaskActivity::query()
                ->where('task_id', $task->getKey())
                ->where('event_type', 'status_changed')
                ->count(),
        );
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_unrelated_user_receives_403_and_cannot_change_task_status(): void
    {
        $owner = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
        ]);

        $this->actingAs($unrelatedUser)
            ->patchJson(route('tasks.status.update', ['task' => $task]), [
                'status' => TaskStatus::Completed->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'status' => TaskStatus::Open->value,
        ]);
        $this->assertDatabaseMissing('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'status_changed',
        ]);
    }
}
