<?php

namespace Tests\Feature\Support\Metrics;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Metrics\ProjectMetrics;
use App\Support\Metrics\TaskMetrics;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskMetricsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_task_counts_exclude_deleted_tasks_and_completed_work_from_open_deadline_metrics(): void
    {
        $this->travelTo('2026-11-13 12:00:00');
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();

        Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::Open,
            'due_date' => '2026-11-12',
        ]);
        $assignedTask = Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::InProgress,
            'due_date' => '2026-11-13',
        ]);
        $assignedTask->assignees()->attach($user);
        Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::Completed,
            'due_date' => '2026-11-12',
        ]);
        $deletedTask = Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::Open,
            'due_date' => '2026-11-13',
        ]);
        $deletedTask->delete();

        $counts = app(TaskMetrics::class)->counts([
            'user_id' => $user->getKey(),
            'today' => '2026-11-13',
        ]);

        $this->assertSame(3, $counts['total']);
        $this->assertSame(1, $counts['completed']);
        $this->assertSame(2, $counts['open']);
        $this->assertSame(1, $counts['in_progress']);
        $this->assertSame(1, $counts['overdue']);
        $this->assertSame(1, $counts['due_today']);
        $this->assertSame(33, $counts['completion_rate']);
        $this->assertSame(3, $counts['created']);
        $this->assertSame(1, $counts['assigned_open']);
    }

    public function test_project_progress_is_the_rounded_average_of_non_deleted_task_progress(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        $emptyProject = Project::factory()->for($user, 'owner')->create();
        Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::InProgress,
            'progress' => 25,
        ]);
        Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::Completed,
            'progress' => 100,
        ]);
        $deletedTask = Task::factory()->for($project)->create([
            'created_by_id' => $user->getKey(),
            'status' => TaskStatus::Completed,
            'progress' => 100,
        ]);
        $deletedTask->delete();

        $progress = app(ProjectMetrics::class)->progress();

        $this->assertSame(63, $progress[$project->getKey()]);
        $this->assertSame(0, $progress[$emptyProject->getKey()]);
    }
}
