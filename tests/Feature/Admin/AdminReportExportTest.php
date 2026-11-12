<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReportExportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_can_download_a_csv_with_formula_cells_neutralized(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => '=HYPERLINK("https://example.invalid")',
            'email' => 'formula@example.invalid',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', 'users'))
            ->assertOk()
            ->assertDownload()
            ->assertStreamed();

        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));

        $this->assertSame('Name', $rows[0][1]);
        $this->assertContains("'=HYPERLINK(\"https://example.invalid\")", array_column($rows, 1));
    }

    public function test_non_admin_users_cannot_export_workspace_data(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)
            ->get(route('admin.reports.export', 'users'))
            ->assertForbidden();
    }

    public function test_admin_can_export_tasks_projects_and_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create(['email' => 'export-owner@example.invalid']);
        $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Export Project']);
        Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Export Task',
        ]);
        AuditLog::factory()->create([
            'user_id' => $owner->getKey(),
            'action' => 'task.created',
            'description' => 'Export activity',
        ]);

        $this->actingAs($admin);

        foreach ([
            'tasks' => 'Export Task',
            'projects' => 'Export Project',
            'audit' => 'Export activity',
        ] as $dataset => $expectedContent) {
            $response = $this->get(route('admin.reports.export', $dataset))
                ->assertOk()
                ->assertDownload()
                ->assertStreamed();

            $this->assertStringContainsString($expectedContent, $response->streamedContent());
        }
    }

    public function test_unknown_export_datasets_return_not_found(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.reports.export', 'secrets'))
            ->assertNotFound();
    }
}
