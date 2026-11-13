<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Exceptions\ProgressConflictException;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use App\Support\AuditContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AuditContext $auditContext,
    ) {}

    /**
     * @param  array{project: int|string, title: string, description?: ?string, priority: string, status: string, due_date: string, assignees?: array<int, int|string>}  $attributes
     */
    public function create(User $actor, array $attributes): Task
    {
        $task = $this->auditContext->runFor(Task::class, fn (): Task => DB::transaction(function () use ($actor, $attributes): Task {
            $status = TaskStatus::from($attributes['status']);
            $task = $actor->createdTasks()->make([
                'project_id' => $attributes['project'],
                'title' => $attributes['title'],
                'description' => $attributes['description'] ?? null,
                'priority' => $attributes['priority'],
                'status' => $status,
                'due_date' => $attributes['due_date'],
                'progress' => 0,
            ]);

            $this->applyProgressRules($task, $actor, TaskStatus::Open, 0, $status);
            $task->save();
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'created',
                'to_status' => $task->status->value,
                'metadata' => [
                    'progress' => $task->progress,
                    'progress_label' => $this->stageLabel($task->progress),
                ],
            ]);
            $this->auditLogger->record('task.created', $task, 'Task created: '.$task->title, [
                'status' => $task->status->value,
                'priority' => $task->priority->value,
                'progress' => $task->progress,
            ], $actor);
            $this->syncAssignees($task, $actor, $attributes['assignees'] ?? [], false);

            return $this->loadForReturn($task);
        }));

        $this->notifyUsers($task, $task->assignees->modelKeys(), $actor, 'task_assigned', 'task_assignments');

        return $task;
    }

    /**
     * @param  array{project: int|string, title: string, description?: ?string, priority: string, status: string, due_date: string, assignees?: array<int, int|string>}  $attributes
     */
    public function update(Task $task, User $actor, array $attributes): Task
    {
        $previousAssigneeIds = [];
        $previousStatus = TaskStatus::Open;
        $updatedTask = $this->auditContext->runFor(Task::class, function () use (
            $task,
            $actor,
            $attributes,
            &$previousAssigneeIds,
            &$previousStatus,
        ): Task {
            return DB::transaction(function () use (
                $task,
                $actor,
                $attributes,
                &$previousAssigneeIds,
                &$previousStatus,
            ): Task {
                $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
                $previousStatus = $task->status;
                $previousProgress = (int) $task->progress;
                $previousAssigneeIds = $task->assignees()->pluck('users.id')->all();
                $status = TaskStatus::from($attributes['status']);

                $task->fill(Arr::only($attributes, ['title', 'description', 'priority', 'due_date']));
                $task->project_id = $attributes['project'];
                $this->applyProgressRules($task, $actor, $previousStatus, $previousProgress, $status);
                $changedFields = array_values(array_intersect(array_keys($task->getDirty()), [
                    'project_id',
                    'title',
                    'description',
                    'priority',
                    'due_date',
                ]));
                $task->save();
                $this->syncAssignees($task, $actor, $attributes['assignees'] ?? []);

                $this->recordProgressActivity($task, $actor, $previousStatus, $previousProgress, 'update');

                if ($changedFields !== []) {
                    $task->activities()->create([
                        'user_id' => $actor->getKey(),
                        'event_type' => 'updated',
                        'metadata' => ['changed_fields' => $changedFields],
                    ]);
                    $this->auditLogger->record('task.updated', $task, 'Task updated: '.$task->title, [
                        'changed_fields' => $changedFields,
                    ], $actor);
                }

                return $this->loadForReturn($task);
            });
        });

        $currentAssigneeIds = $updatedTask->assignees->modelKeys();
        $this->notifyUsers(
            $updatedTask,
            array_values(array_diff($currentAssigneeIds, $previousAssigneeIds)),
            $actor,
            'task_assigned',
            'task_assignments',
        );

        if ($previousStatus !== $updatedTask->status) {
            $this->notifyStatusChange($updatedTask, $actor);
        }

        return $updatedTask;
    }

    public function changeStatus(Task $task, User $actor, TaskStatus $status): Task
    {
        $previousStatus = TaskStatus::Open;
        $previousProgress = 0;
        $updatedTask = $this->auditContext->runFor(Task::class, function () use (
            $task,
            $actor,
            $status,
            &$previousStatus,
            &$previousProgress,
        ): Task {
            return DB::transaction(function () use (
                $task,
                $actor,
                $status,
                &$previousStatus,
                &$previousProgress,
            ): Task {
                $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
                $previousStatus = $task->status;
                $previousProgress = (int) $task->progress;

                if ($previousStatus === $status) {
                    return $this->loadForReturn($task);
                }

                $this->applyProgressRules($task, $actor, $previousStatus, $previousProgress, $status);
                $task->save();
                $this->recordProgressActivity($task, $actor, $previousStatus, $previousProgress, 'status');

                return $this->loadForReturn($task);
            });
        });

        if ($previousStatus !== $updatedTask->status) {
            $this->notifyStatusChange($updatedTask, $actor);
        }

        return $updatedTask;
    }

    public function setProgress(Task $task, int $progress, int $expectedProgress, User $actor): Task
    {
        $previousStatus = TaskStatus::Open;
        $previousProgress = 0;
        $updatedTask = $this->auditContext->runFor(Task::class, function () use (
            $task,
            $progress,
            $expectedProgress,
            $actor,
            &$previousStatus,
            &$previousProgress,
        ): Task {
            return DB::transaction(function () use (
                $task,
                $progress,
                $expectedProgress,
                $actor,
                &$previousStatus,
                &$previousProgress,
            ): Task {
                $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
                $previousStatus = $task->status;
                $previousProgress = (int) $task->progress;

                if ($previousProgress !== $expectedProgress) {
                    throw new ProgressConflictException($this->loadForReturn($task));
                }

                if ($previousProgress === $progress) {
                    return $this->loadForReturn($task);
                }

                $this->applyProgressRules($task, $actor, $previousStatus, $previousProgress, requestedProgress: $progress);
                $task->save();
                $this->recordProgressActivity($task, $actor, $previousStatus, $previousProgress, 'progress');

                return $this->loadForReturn($task);
            });
        });

        if ($previousStatus !== $updatedTask->status) {
            $this->notifyStatusChange($updatedTask, $actor);
        }

        return $updatedTask;
    }

    public function assign(Task $task, User $actor, User $assignee): Task
    {
        $wasAssigned = false;
        $updatedTask = $this->auditContext->runFor(Task::class, function () use (
            $task,
            $actor,
            $assignee,
            &$wasAssigned,
        ): Task {
            return DB::transaction(function () use (
                $task,
                $actor,
                $assignee,
                &$wasAssigned,
            ): Task {
                $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
                $assigneeIds = $task->assignees()->pluck('users.id')->all();
                $wasAssigned = in_array($assignee->getKey(), $assigneeIds, true);
                $assigneeIds[] = $assignee->getKey();
                $this->syncAssignees($task, $actor, array_values(array_unique($assigneeIds)));

                return $this->loadForReturn($task);
            });
        });

        if (! $wasAssigned) {
            $this->notifyUsers($updatedTask, [$assignee->getKey()], $actor, 'task_assigned', 'task_assignments');
        }

        return $updatedTask;
    }

    public function unassign(Task $task, User $actor, User $assignee): Task
    {
        return $this->auditContext->runFor(Task::class, fn (): Task => DB::transaction(function () use ($task, $actor, $assignee): Task {
            $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
            $assigneeIds = array_values(array_diff(
                $task->assignees()->pluck('users.id')->all(),
                [$assignee->getKey()],
            ));
            $this->syncAssignees($task, $actor, $assigneeIds);

            return $this->loadForReturn($task);
        }));
    }

    public function comment(Task $task, User $actor, string $body): TaskComment
    {
        $comment = $this->auditContext->runFor(Task::class, fn (): TaskComment => DB::transaction(function () use ($task, $actor, $body): TaskComment {
            $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
            $comment = $task->comments()->create([
                'user_id' => $actor->getKey(),
                'body' => $body,
            ]);
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'commented',
                'metadata' => ['comment_id' => $comment->getKey()],
            ]);
            $this->auditLogger->record('task.commented', $task, 'A comment was added to task: '.$task->title, [
                'comment_id' => $comment->getKey(),
            ], $actor);
            $task->touch();

            return $comment->load('user:id,name');
        }));

        $task = Task::query()
            ->with(['project:id,name,owner_id', 'assignees:id'])
            ->findOrFail($task->getKey());
        $recipientIds = array_unique([
            $task->created_by_id,
            $task->project->owner_id,
            ...$task->assignees->modelKeys(),
        ]);
        $this->notifyUsers($task, $recipientIds, $actor, 'task_comment', 'team_activity');

        return $comment;
    }

    public function delete(Task $task, User $actor): void
    {
        $this->auditContext->runFor(Task::class, function () use ($task, $actor): void {
            DB::transaction(function () use ($task, $actor): void {
                $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
                $task->activities()->create([
                    'user_id' => $actor->getKey(),
                    'event_type' => 'deleted',
                ]);
                $this->auditLogger->record(
                    'task.deleted',
                    $task,
                    'Task moved to trash: '.$task->title,
                    [],
                    $actor,
                );
                $task->delete();
            });
        });
    }

    public function restore(Task $task, User $actor): Task
    {
        return $this->auditContext->runFor(Task::class, fn (): Task => DB::transaction(function () use ($task, $actor): Task {
            $task = Task::onlyTrashed()->lockForUpdate()->findOrFail($task->getKey());
            $task->restore();
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'restored',
            ]);
            $this->auditLogger->record('task.restored', $task, 'Task restored: '.$task->title, [], $actor);

            return $this->loadForReturn($task);
        }));
    }

    /**
     * @param  array<int, int|string>  $assigneeIds
     */
    private function syncAssignees(Task $task, User $actor, array $assigneeIds, bool $touchTask = true): void
    {
        $changes = $task->assignees()->sync($assigneeIds);

        foreach ($changes['attached'] as $assigneeId) {
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'assigned',
                'metadata' => ['assignee_id' => $assigneeId],
            ]);
            $this->auditLogger->record('task.assigned', $task, 'A user was assigned to task: '.$task->title, [
                'assignee_id' => $assigneeId,
            ], $actor);
        }

        foreach ($changes['detached'] as $assigneeId) {
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'unassigned',
                'metadata' => ['assignee_id' => $assigneeId],
            ]);
            $this->auditLogger->record('task.unassigned', $task, 'A user was unassigned from task: '.$task->title, [
                'assignee_id' => $assigneeId,
            ], $actor);
        }

        if ($touchTask && ($changes['attached'] !== [] || $changes['detached'] !== [])) {
            $task->touch();
        }
    }

    private function applyProgressRules(
        Task $task,
        User $actor,
        TaskStatus $previousStatus,
        int $previousProgress,
        ?TaskStatus $requestedStatus = null,
        ?int $requestedProgress = null,
    ): void {
        if ($requestedProgress !== null) {
            $task->progress = $requestedProgress;

            if ($requestedProgress === 100) {
                $this->markCompleted($task, $actor, $previousStatus, $previousProgress);
            } else {
                if ($previousStatus === TaskStatus::Completed) {
                    $task->completed_at = null;
                    $task->completed_by_id = null;
                    $task->progress_before_complete = null;
                }

                $task->status = $requestedProgress === 0 ? TaskStatus::Open : TaskStatus::InProgress;
            }
        } elseif ($requestedStatus === TaskStatus::Completed) {
            $this->markCompleted($task, $actor, $previousStatus, $previousProgress);
        } elseif ($previousStatus === TaskStatus::Completed) {
            // Restore the last in-progress stage instead of resetting completed work.
            $restoredProgress = $task->progress_before_complete
                ?? ($requestedStatus === TaskStatus::InProgress ? 25 : 0);

            $task->progress = $restoredProgress;
            $task->status = $restoredProgress === 0 ? TaskStatus::Open : TaskStatus::InProgress;
            $task->completed_at = null;
            $task->completed_by_id = null;
            $task->progress_before_complete = null;
        } else {
            $task->status = $requestedStatus ?? $task->status;

            if ($task->status === TaskStatus::Open) {
                $task->progress = 0;
            } elseif ($task->status === TaskStatus::InProgress && (int) $task->progress === 0) {
                $task->progress = 25;
            } elseif ((int) $task->progress === 100) {
                $this->markCompleted($task, $actor, $previousStatus, $previousProgress);
            }
        }

        if ((int) $task->progress !== $previousProgress) {
            $task->progress_updated_at = now();
            $task->progress_updated_by_id = $actor->getKey();
        }
    }

    private function markCompleted(Task $task, User $actor, TaskStatus $previousStatus, int $previousProgress): void
    {
        if ($previousStatus !== TaskStatus::Completed) {
            if ($previousProgress < 100) {
                // Keep the prior stage so reopening the task can resume from it.
                $task->progress_before_complete = $previousProgress;
            }

            $task->completed_at = now();
            $task->completed_by_id = $actor->getKey();
        }

        $task->status = TaskStatus::Completed;
        $task->progress = 100;
    }

    private function recordProgressActivity(
        Task $task,
        User $actor,
        TaskStatus $previousStatus,
        int $previousProgress,
        string $source,
    ): void {
        $progressChanged = $previousProgress !== (int) $task->progress;
        $statusChanged = $previousStatus !== $task->status;

        if (! $progressChanged && ! $statusChanged) {
            return;
        }

        $metadata = [
            'from_progress' => $previousProgress,
            'to_progress' => (int) $task->progress,
            'from_progress_label' => $this->stageLabel($previousProgress),
            'to_progress_label' => $this->stageLabel((int) $task->progress),
            'message' => $this->progressActivityMessage($previousStatus, $previousProgress, $task, $source),
            'source' => $source,
        ];

        $task->activities()->create([
            'user_id' => $actor->getKey(),
            'event_type' => $statusChanged ? 'status_changed' : 'progress_changed',
            'from_status' => $statusChanged ? $previousStatus->value : null,
            'to_status' => $statusChanged ? $task->status->value : null,
            'metadata' => $metadata,
        ]);

        if ($statusChanged) {
            $this->auditLogger->record('task.status_changed', $task, 'Task status changed: '.$task->title, [
                'from_status' => $previousStatus->value,
                'to_status' => $task->status->value,
            ], $actor);
        }

        if ($progressChanged) {
            $this->auditLogger->record('task.progress_changed', $task, 'Task progress changed: '.$task->title, [
                'from_progress' => $previousProgress,
                'to_progress' => (int) $task->progress,
            ], $actor);
        }
    }

    private function progressActivityMessage(
        TaskStatus $previousStatus,
        int $previousProgress,
        Task $task,
        string $source,
    ): string {
        if ($source === 'progress' && $previousStatus === TaskStatus::Completed) {
            return 'reopened this task. Progress restored to '.$task->progress.'%.';
        }

        if ($task->status === TaskStatus::Completed) {
            return 'marked this task as complete. Progress set to 100% automatically.';
        }

        if ($previousStatus === TaskStatus::Completed) {
            return 'reopened this task. Progress restored to '.$task->progress.'%.';
        }

        if ($previousStatus !== $task->status && ! ((int) $task->progress !== $previousProgress)) {
            return 'changed the status to '.$task->status->value.'.';
        }

        return 'moved progress from '.$previousProgress.'% to '.$task->progress.'% ('.$this->stageLabel((int) $task->progress).').';
    }

    private function stageLabel(int $progress): string
    {
        foreach (config('progress.stages', []) as $stage) {
            if ($stage['value'] === $progress) {
                return $stage['label'];
            }
        }

        return 'Unknown';
    }

    private function loadForReturn(Task $task): Task
    {
        return $task->load([
            'project:id,name,owner_id',
            'project.owner:id,name',
            'assignees:id,name',
            'creator:id,name',
            'progressUpdater:id,name',
            'activities.user:id,name',
            'comments.user:id,name',
        ]);
    }

    private function notifyStatusChange(Task $task, User $actor): void
    {
        $this->notifyUsers(
            $task,
            array_unique([
                ...$task->assignees->modelKeys(),
                $task->created_by_id,
                $task->project->owner_id,
            ]),
            $actor,
            'task_status_changed',
            'task_status_updates',
        );
    }

    /**
     * @param  array<int, int|string>  $recipientIds
     */
    private function notifyUsers(
        Task $task,
        array $recipientIds,
        User $actor,
        string $event,
        string $preference,
    ): void {
        User::query()
            ->active()
            ->whereIn('id', array_diff($recipientIds, [$actor->getKey()]))
            ->get()
            ->each(function (User $recipient) use ($task, $actor, $event, $preference): void {
                if (($recipient->settings['notifications'][$preference] ?? true) === true) {
                    $recipient->notify(new TaskActivityNotification($event, $task, $actor));
                }
            });
    }
}
