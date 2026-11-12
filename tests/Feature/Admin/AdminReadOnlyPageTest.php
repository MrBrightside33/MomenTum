<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadOnlyPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_pages_render_workspace_data_from_the_database(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['name' => 'Project Owner']);
        $project = Project::factory()->for($owner, 'owner')->create([
            'name' => 'Quarterly Planning Project',
        ]);
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Quarterly Planning Task',
        ]);
        AuditLog::factory()->create([
            'user_id' => $owner->getKey(),
            'action' => 'task.created',
            'description' => 'Quarterly planning activity recorded.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.overview'))
            ->assertOk()
            ->assertSeeText('Admin overview')
            ->assertSeeText('Quarterly planning activity recorded.');

        $this->get(route('admin.tasks.index'))
            ->assertOk()
            ->assertSeeText($task->title);

        $this->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSeeText($project->name);

        $this->get(route('admin.projects.index', ['status' => 'in_progress']))
            ->assertOk()
            ->assertSeeText($project->name)
            ->assertSeeText('In progress');

        $this->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSeeText('Account status');

        $this->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSeeText('Quarterly planning activity recorded.');

        $this->get(route('admin.search', ['q' => 'Quarterly Planning Project']))
            ->assertOk()
            ->assertSeeText($project->name)
            ->assertSeeText('In progress');

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSeeText('Role permissions');
    }

    public function test_non_admin_users_cannot_review_admin_tasks(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.tasks.index'))
            ->assertForbidden();
    }

    public function test_audit_security_filter_excludes_non_authentication_events(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::factory()->create([
            'action' => 'auth.login_failed',
            'description' => 'Failed login should appear.',
        ]);
        AuditLog::factory()->create([
            'action' => 'task.created',
            'description' => 'Task creation should be filtered out.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit.index', ['group' => 'security']))
            ->assertOk()
            ->assertSeeText('Failed login should appear.')
            ->assertDontSeeText('Task creation should be filtered out.');
    }
}
