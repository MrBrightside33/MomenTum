<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeleteTaskTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_task_owner_moves_the_persisted_task_to_trash_and_receives_its_restore_url(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
        ]);
        $task->assignees()->attach($assignee);
        $task->activities()->create([
            'user_id' => $owner->getKey(),
            'event_type' => 'created',
        ]);
        $task->comments()->create([
            'user_id' => $owner->getKey(),
            'body' => 'Initial task comment.',
        ]);

        $this->actingAs($owner)
            ->deleteJson(route('tasks.destroy', ['task' => $task]))
            ->assertOk()
            ->assertJsonPath('message', 'Task moved to trash')
            ->assertJsonPath('task_id', $task->getKey())
            ->assertJsonPath('restore_url', route('tasks.restore', ['task' => $task]));

        $this->assertSoftDeleted($task);
        $this->assertDatabaseHas('task_assignees', ['task_id' => $task->getKey()]);
        $this->assertDatabaseHas('task_activities', ['task_id' => $task->getKey()]);
        $this->assertDatabaseHas('task_comments', ['task_id' => $task->getKey()]);
        $this->actingAs($owner)->getJson(route('tasks.show', ['task' => $task]))->assertNotFound();
    }

    public function test_unrelated_user_receives_403_and_task_remains_unchanged(): void
    {
        $owner = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
        ]);

        $this->actingAs($unrelatedUser)
            ->deleteJson(route('tasks.destroy', ['task' => $task]))
            ->assertForbidden();

        $this->assertModelExists($task);
    }

    public function test_task_owner_restores_a_trashed_task_with_its_history(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
        ]);
        $task->assignees()->attach($assignee);
        $task->comments()->create([
            'user_id' => $owner->getKey(),
            'body' => 'Keep this comment after restoring.',
        ]);
        $task->delete();

        $this->actingAs($owner)
            ->postJson(route('tasks.restore', ['task' => $task]))
            ->assertOk()
            ->assertJsonPath('message', 'Task restored')
            ->assertJsonPath('task_id', $task->getKey())
            ->assertJsonPath('task.id', $task->getKey());

        $this->assertNotSoftDeleted($task);
        $this->assertDatabaseHas('task_assignees', ['task_id' => $task->getKey()]);
        $this->assertDatabaseHas('task_comments', [
            'task_id' => $task->getKey(),
            'body' => 'Keep this comment after restoring.',
        ]);
    }

    public function test_unrelated_user_cannot_restore_a_trashed_task(): void
    {
        $owner = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
        ]);
        $task->delete();

        $this->actingAs($unrelatedUser)
            ->postJson(route('tasks.restore', ['task' => $task]))
            ->assertForbidden();

        $this->assertSoftDeleted($task);
    }

    public function test_pruning_permanently_removes_only_tasks_past_the_retention_period(): void
    {
        config(['app.task_retention_days' => 30]);
        $expiredTask = Task::factory()->create();
        $recentTask = Task::factory()->create();
        $expiredTask->delete();
        $recentTask->delete();
        $expiredTask->forceFill(['deleted_at' => now()->subDays(31)])->saveQuietly();

        $this->artisan('model:prune', ['--model' => [Task::class]])
            ->assertExitCode(0);

        $this->assertModelMissing($expiredTask);
        $this->assertSoftDeleted($recentTask);
    }
}
