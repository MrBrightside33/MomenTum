<?php

namespace Tests\Feature\Admin;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admins_can_review_tasks_and_projects_but_cannot_change_them(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create(['created_by_id' => $owner->getKey()]);

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Task::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $task));
        $this->assertFalse(Gate::forUser($admin)->allows('create', Task::class));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $task));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $task));
        $this->assertFalse(Gate::forUser($admin)->allows('changeStatus', $task));
        $this->assertFalse(Gate::forUser($admin)->allows('addComment', $task));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Project::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $project));
        $this->assertFalse(Gate::forUser($admin)->allows('create', Project::class));
        $this->assertFalse(Gate::forUser($admin)->allows('update', $project));
        $this->assertFalse(Gate::forUser($admin)->allows('archive', $project));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $project));
    }

    public function test_managers_manage_tasks_and_archive_projects_while_members_keep_own_task_rights(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->create();
        $project = Project::factory()->for($manager, 'owner')->create();
        $task = Task::factory()->for($project)->create(['created_by_id' => $manager->getKey()]);
        $memberTask = Task::factory()->for($project)->create(['created_by_id' => $member->getKey()]);

        $this->assertTrue(Gate::forUser($manager)->allows('view', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Task::class));
        $this->assertTrue(Gate::forUser($manager)->allows('update', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('delete', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('changeStatus', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('addComment', $task));
        $this->assertTrue(Gate::forUser($manager)->allows('create', Project::class));
        $this->assertTrue(Gate::forUser($manager)->allows('archive', $project));

        $this->assertTrue(Gate::forUser($member)->allows('view', $memberTask));
        $this->assertTrue(Gate::forUser($member)->allows('update', $memberTask));
        $this->assertTrue(Gate::forUser($member)->allows('delete', $memberTask));
        $this->assertFalse(Gate::forUser($member)->allows('update', $task));
        $this->assertFalse(Gate::forUser($member)->allows('delete', $task));
    }
}
