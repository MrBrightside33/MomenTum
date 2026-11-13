<?php

namespace Tests\Feature\Services;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskAuditTrailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assignment_changes_are_audited_and_touch_the_task_timestamp(): void
    {
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::factory()->for($actor, 'owner')->create();
        $task = Task::factory()->for($project)->create(['created_by_id' => $actor->getKey()]);
        $originalUpdatedAt = $task->fresh()->updated_at;
        $service = app(TaskService::class);

        $this->travel(2)->seconds();
        $service->assign($task, $actor, $assignee);

        $this->assertGreaterThan(
            $originalUpdatedAt->getTimestamp(),
            $task->fresh()->updated_at->getTimestamp(),
        );
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'assigned',
            'metadata->assignee_id' => $assignee->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.assigned',
            'subject_id' => $task->getKey(),
            'user_id' => $actor->getKey(),
        ]);
        $this->assertDatabaseCount('notifications', 1);
        $service->assign($task, $actor, $assignee);
        $this->assertDatabaseCount('notifications', 1);

        $service->unassign($task, $actor, $assignee);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'unassigned',
            'metadata->assignee_id' => $assignee->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.unassigned',
            'subject_id' => $task->getKey(),
            'user_id' => $actor->getKey(),
        ]);
        $this->assertDatabaseMissing('task_assignees', [
            'task_id' => $task->getKey(),
            'user_id' => $assignee->getKey(),
        ]);
    }

    public function test_comments_and_trash_actions_are_service_managed_without_logging_comment_text(): void
    {
        $actor = User::factory()->create();
        $project = Project::factory()->for($actor, 'owner')->create();
        $task = Task::factory()->for($project)->create(['created_by_id' => $actor->getKey()]);
        $commentBody = 'Private task discussion content.';
        $service = app(TaskService::class);

        $comment = $service->comment($task, $actor, $commentBody);
        $service->delete($task, $actor);
        $service->restore($task, $actor);

        $this->assertDatabaseHas('task_comments', [
            'id' => $comment->getKey(),
            'body' => $commentBody,
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'commented',
            'metadata->comment_id' => $comment->getKey(),
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'deleted',
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'restored',
        ]);

        $commentAudit = AuditLog::query()
            ->where('action', 'task.commented')
            ->where('subject_id', $task->getKey())
            ->firstOrFail();

        $this->assertSame(['comment_id' => $comment->getKey()], $commentAudit->metadata);
        $this->assertStringNotContainsString($commentBody, $commentAudit->description);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.deleted',
            'subject_id' => $task->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.restored',
            'subject_id' => $task->getKey(),
        ]);
        $this->assertNotSoftDeleted($task);
    }

    public function test_direct_model_changes_are_audited_and_touch_parent_project(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'status' => TaskStatus::Open,
            'progress' => 0,
        ]);
        $originalProjectUpdatedAt = $project->fresh()->updated_at;

        $this->travel(2)->seconds();
        $task->forceFill(['status' => TaskStatus::InProgress])->save();

        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
        $this->assertSame(25, $task->fresh()->progress);
        $this->assertGreaterThan(
            $originalProjectUpdatedAt->getTimestamp(),
            $project->fresh()->updated_at->getTimestamp(),
        );
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'status_changed',
            'from_status' => TaskStatus::Open->value,
            'to_status' => TaskStatus::InProgress->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.status_changed',
            'subject_id' => $task->getKey(),
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'task.progress_changed',
            'subject_id' => $task->getKey(),
        ]);

        $owner->forceFill(['settings' => ['timezone' => 'Asia/Singapore']])->save();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.preferences_updated',
            'subject_id' => $owner->getKey(),
            'metadata->changed_fields' => json_encode(['timezone']),
        ]);

        $newProject = Project::factory()->for($owner, 'owner')->create();
        $previousProjectUpdatedAt = $project->fresh()->updated_at;
        $newProjectUpdatedAt = $newProject->fresh()->updated_at;
        $this->travel(2)->seconds();
        $task->forceFill(['project_id' => $newProject->getKey()])->save();

        $this->assertGreaterThan(
            $previousProjectUpdatedAt->getTimestamp(),
            $project->fresh()->updated_at->getTimestamp(),
        );
        $this->assertGreaterThan(
            $newProjectUpdatedAt->getTimestamp(),
            $newProject->fresh()->updated_at->getTimestamp(),
        );

        $project->forceFill(['status' => ProjectStatus::Archived])->save();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'project.archived',
            'subject_id' => $project->getKey(),
        ]);
    }

    public function test_task_service_writes_are_not_duplicated_by_observers(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();

        $task = app(TaskService::class)->create($owner, [
            'project' => $project->getKey(),
            'title' => 'A service-created task',
            'priority' => 'Medium',
            'status' => TaskStatus::Open->value,
            'due_date' => today()->toDateString(),
        ]);

        $this->assertSame(
            1,
            TaskActivity::query()->where('task_id', $task->getKey())->where('event_type', 'created')->count(),
        );
        $this->assertSame(
            1,
            AuditLog::query()
                ->where('action', 'task.created')
                ->where('subject_id', $task->getKey())
                ->count(),
        );
    }

    public function test_task_updates_audit_only_changed_fields_and_skip_no_op_updates(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Original task',
        ]);
        $attributes = [
            'project' => $project->getKey(),
            'title' => 'Updated task',
            'description' => 'A clearer task description.',
            'priority' => 'High',
            'status' => TaskStatus::Open->value,
            'due_date' => today()->addDays(2)->toDateString(),
        ];
        $service = app(TaskService::class);

        $service->update($task, $owner, $attributes);

        $auditLog = AuditLog::query()
            ->where('action', 'task.updated')
            ->where('subject_id', $task->getKey())
            ->firstOrFail();
        $this->assertSame(
            ['title', 'description', 'priority', 'due_date'],
            $auditLog->metadata['changed_fields'],
        );

        $service->update($task, $owner, $attributes);

        $this->assertSame(
            1,
            AuditLog::query()
                ->where('action', 'task.updated')
                ->where('subject_id', $task->getKey())
                ->count(),
        );
    }

    public function test_task_changes_complete_when_audit_storage_is_unavailable(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create();
        $task = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Before audit outage',
        ]);
        AuditLog::creating(static function (): void {
            throw new \RuntimeException('Audit storage unavailable.');
        });

        $task->forceFill(['title' => 'Saved during audit outage'])->save();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->getKey(),
            'title' => 'Saved during audit outage',
        ]);
        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->getKey(),
            'event_type' => 'updated',
        ]);
    }
}
