<?php

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Support\AuditContext;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TaskObserver
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AuditContext $auditContext,
    ) {}

    public function saving(Task $task): void
    {
        $wasCompleted = $task->getRawOriginal('status') === TaskStatus::Completed->value;
        $previousProgress = (int) ($task->getRawOriginal('progress') ?? 0);
        $progressBeforeCorrection = (int) ($task->progress ?? 0);

        if ($task->status === TaskStatus::Completed) {
            if (! $wasCompleted && $previousProgress < 100) {
                $task->progress_before_complete = $previousProgress;
            }

            $task->progress = 100;
            $task->completed_at ??= now();

            if (! $wasCompleted || $task->completed_by_id === null) {
                $task->completed_by_id = Auth::id();
            }
        } elseif ((int) $task->progress === 100) {
            if (! $wasCompleted && $previousProgress < 100) {
                $task->progress_before_complete = $previousProgress;
            }

            $task->status = TaskStatus::Completed;
            $task->completed_at ??= now();
            $task->completed_by_id ??= Auth::id();
        } elseif ($task->status === TaskStatus::InProgress && (int) $task->progress === 0) {
            $task->progress = 25;
        } elseif ($task->status === TaskStatus::Open && (int) $task->progress > 0) {
            $task->progress = 0;
        }

        if ($progressBeforeCorrection !== (int) $task->progress) {
            $task->progress_updated_at ??= now();
            $task->progress_updated_by_id ??= Auth::id();
        }
    }

    public function created(Task $task): void
    {
        $this->touchProject($task);

        if ($this->auditContext->isServiceWrite(Task::class)) {
            return;
        }

        $this->recordActivity($task, 'created', toStatus: $task->status->value, metadata: [
            'progress' => (int) $task->progress,
        ]);
        $this->auditLogger->record('task.created', $task, 'Task created: '.$task->title, [
            'status' => $task->status->value,
            'progress' => (int) $task->progress,
        ]);
    }

    public function updated(Task $task): void
    {
        $previous = $task->getPrevious();

        if ($task->wasChanged(['status', 'progress', 'project_id'])) {
            $this->touchProject($task);

            if ($task->wasChanged('project_id') && isset($previous['project_id'])) {
                $this->touchProject($task, $previous['project_id']);
            }
        }

        if ($this->auditContext->isServiceWrite(Task::class)) {
            return;
        }

        $previousStatus = $this->statusValue($previous['status'] ?? null);
        $statusChanged = $task->wasChanged('status');
        $progressChanged = $task->wasChanged('progress');
        $metadata = [];

        if ($statusChanged) {
            $metadata['from_status'] = $previousStatus;
            $metadata['to_status'] = $task->status->value;
        }

        if ($progressChanged) {
            $metadata['from_progress'] = (int) ($previous['progress'] ?? 0);
            $metadata['to_progress'] = (int) $task->progress;
        }

        if ($statusChanged || $progressChanged) {
            $this->recordActivity(
                $task,
                $statusChanged ? 'status_changed' : 'progress_changed',
                $statusChanged ? $previousStatus : null,
                $statusChanged ? $task->status->value : null,
                $metadata,
            );

            if ($statusChanged) {
                $this->auditLogger->record('task.status_changed', $task, 'Task status changed: '.$task->title, [
                    'from_status' => $previousStatus,
                    'to_status' => $task->status->value,
                ]);
            }

            if ($progressChanged) {
                $this->auditLogger->record('task.progress_changed', $task, 'Task progress changed: '.$task->title, [
                    'from_progress' => (int) ($previous['progress'] ?? 0),
                    'to_progress' => (int) $task->progress,
                ]);
            }
        }

        $changedFields = array_values(array_intersect(array_keys($task->getChanges()), [
            'project_id',
            'created_by_id',
            'title',
            'description',
            'priority',
            'due_date',
        ]));

        if ($changedFields !== []) {
            $this->recordActivity($task, 'updated', metadata: ['changed_fields' => $changedFields]);
            $this->auditLogger->record('task.updated', $task, 'Task updated: '.$task->title, [
                'changed_fields' => $changedFields,
            ]);
        }
    }

    public function deleted(Task $task): void
    {
        $this->touchProject($task);

        if ($task->isForceDeleting() || $this->auditContext->isServiceWrite(Task::class)) {
            return;
        }

        $this->recordActivity($task, 'deleted');
        $this->auditLogger->record('task.deleted', $task, 'Task moved to trash: '.$task->title);
    }

    public function restored(Task $task): void
    {
        $this->touchProject($task);

        if ($this->auditContext->isServiceWrite(Task::class)) {
            return;
        }

        $this->recordActivity($task, 'restored');
        $this->auditLogger->record('task.restored', $task, 'Task restored: '.$task->title);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordActivity(
        Task $task,
        string $eventType,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        array $metadata = [],
    ): void {
        try {
            $task->activities()->create([
                'user_id' => Auth::id(),
                'event_type' => $eventType,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'metadata' => $metadata,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function touchProject(Task $task, int|string|null $projectId = null): void
    {
        $project = $projectId === null
            ? $task->project()->first()
            : Project::query()->find($projectId);

        if ($project instanceof Project) {
            $project->touch();
        }
    }

    private function statusValue(mixed $status): ?string
    {
        if ($status instanceof TaskStatus) {
            return $status->value;
        }

        return is_string($status) ? $status : null;
    }
}
