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

    public function test_task_owner_deletes_the_persisted_task_and_receives_its_identifier(): void
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
            ->assertJsonPath('message', 'Task deleted')
            ->assertJsonPath('task_id', $task->getKey());

        $this->assertDatabaseMissing('tasks', ['id' => $task->getKey()]);
        $this->assertDatabaseMissing('task_assignees', ['task_id' => $task->getKey()]);
        $this->assertDatabaseMissing('task_activities', ['task_id' => $task->getKey()]);
        $this->assertDatabaseMissing('task_comments', ['task_id' => $task->getKey()]);
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
}
