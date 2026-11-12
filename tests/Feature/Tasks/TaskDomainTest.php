<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskDomainTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_task_and_project_visibility_is_scoped_to_ownership_and_assignment(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $assignee = User::factory()->create();
        $outsider = User::factory()->create();
        $administrator = User::factory()->admin()->create();
        $ownedProject = Project::factory()->for($owner, 'owner')->create();
        $assignedProject = Project::factory()->for($otherOwner, 'owner')->create();
        $privateProject = Project::factory()->for($otherOwner, 'owner')->create();
        $ownedTask = Task::factory()->for($ownedProject)->create([
            'created_by_id' => $owner->getKey(),
        ]);
        $assignedTask = Task::factory()->for($assignedProject)->create([
            'created_by_id' => $otherOwner->getKey(),
        ]);
        $privateTask = Task::factory()->for($privateProject)->create([
            'created_by_id' => $otherOwner->getKey(),
        ]);
        $assignedTask->assignees()->attach($assignee);

        $ownerTaskIds = Task::query()->visibleTo($owner)->pluck('id')->all();
        $assigneeTaskIds = Task::query()->visibleTo($assignee)->pluck('id')->all();
        $outsiderTaskIds = Task::query()->visibleTo($outsider)->pluck('id')->all();

        $this->assertContains($ownedTask->getKey(), $ownerTaskIds);
        $this->assertNotContains($assignedTask->getKey(), $ownerTaskIds);
        $this->assertContains($assignedTask->getKey(), $assigneeTaskIds);
        $this->assertNotContains($privateTask->getKey(), $assigneeTaskIds);
        $this->assertSame([], $outsiderTaskIds);
        $this->assertContains($assignedProject->getKey(), Project::query()->visibleTo($assignee)->pluck('id')->all());
        $this->assertNotContains($privateProject->getKey(), Project::query()->visibleTo($assignee)->pluck('id')->all());
        $this->assertCount(3, Task::query()->visibleTo($administrator)->get());
    }
}
