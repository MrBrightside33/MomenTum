<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskWorkflow
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array{project: int|string, title: string, description?: ?string, priority: string, status: string, due_date: string, assignees?: array<int, int|string>}  $attributes
     */
    public function create(User $actor, array $attributes): Task
    {
        $task = DB::transaction(function () use ($actor, $attributes): Task {
            $status = TaskStatus::from($attributes['status']);
            $task = $actor->createdTasks()->create([
                'project_id' => $attributes['project'],
                'title' => $attributes['title'],
                'description' => $attributes['description'] ?? null,
                'priority' => $attributes['priority'],
                'status' => $status,
                'due_date' => $attributes['due_date'],
                'completed_at' => $status === TaskStatus::Completed ? now() : null,
            ]);

            $assigneeIds = $attributes['assignees'] ?? [];
            $task->assignees()->sync($assigneeIds);
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'created',
                'to_status' => $status->value,
            ]);
            $this->auditLogger->record('task.created', $task, 'Task created: '.$task->title, [
                'status' => $status->value,
                'priority' => $task->priority->value,
            ], $actor);

            return $task->load(['project', 'assignees']);
        });

        $this->notifyUsers(
            $task,
            $task->assignees->pluck('id')->all(),
            $actor,
            'task_assigned',
            'task_assignments',
        );

        return $task;
    }

    /**
     * @param  array{project: int|string, title: string, description?: ?string, priority: string, status: string, due_date: string, assignees?: array<int, int|string>}  $attributes
     */
    public function update(Task $task, User $actor, array $attributes): Task
    {
        $previousAssigneeIds = $task->assignees()->pluck('users.id')->all();
        $previousStatus = $task->status;

        $task = DB::transaction(function () use ($task, $actor, $attributes, $previousStatus): Task {
            $status = TaskStatus::from($attributes['status']);
            $task->fill(Arr::only($attributes, ['title', 'description', 'priority', 'due_date']));
            $task->project_id = $attributes['project'];
            $task->status = $status;
            $task->completed_at = $status === TaskStatus::Completed
                ? ($previousStatus === TaskStatus::Completed ? $task->completed_at : now())
                : null;
            $task->save();
            $task->assignees()->sync($attributes['assignees'] ?? []);

            if ($previousStatus !== $status) {
                $task->activities()->create([
                    'user_id' => $actor->getKey(),
                    'event_type' => 'status_changed',
                    'from_status' => $previousStatus->value,
                    'to_status' => $status->value,
                ]);
                $this->auditLogger->record('task.status_changed', $task, 'Task status changed: '.$task->title, [
                    'from_status' => $previousStatus->value,
                    'to_status' => $status->value,
                ], $actor);
            } else {
                $task->activities()->create([
                    'user_id' => $actor->getKey(),
                    'event_type' => 'updated',
                ]);
                $this->auditLogger->record('task.updated', $task, 'Task updated: '.$task->title, [], $actor);
            }

            return $task->load(['project', 'assignees']);
        });

        $currentAssigneeIds = $task->assignees->pluck('id')->all();
        $newAssigneeIds = array_values(array_diff($currentAssigneeIds, $previousAssigneeIds));

        $this->notifyUsers($task, $newAssigneeIds, $actor, 'task_assigned', 'task_assignments');

        if ($previousStatus !== $task->status) {
            $this->notifyUsers(
                $task,
                array_unique([
                    ...$currentAssigneeIds,
                    $task->created_by_id,
                    $task->project->owner_id,
                ]),
                $actor,
                'task_status_changed',
                'task_status_updates',
            );
        }

        return $task;
    }

    public function changeStatus(Task $task, User $actor, TaskStatus $status): Task
    {
        $statusChanged = false;
        $task = DB::transaction(function () use ($task, $actor, $status, &$statusChanged): Task {
            $task = Task::query()->lockForUpdate()->findOrFail($task->getKey());
            $previousStatus = $task->status;

            if ($previousStatus === $status) {
                return $task->load(['project', 'assignees']);
            }

            $statusChanged = true;
            $task->status = $status;
            $task->completed_at = $status === TaskStatus::Completed ? now() : null;
            $task->save();
            $task->activities()->create([
                'user_id' => $actor->getKey(),
                'event_type' => 'status_changed',
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
            ]);
            $this->auditLogger->record('task.status_changed', $task, 'Task status changed: '.$task->title, [
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
            ], $actor);

            return $task->load(['project', 'assignees']);
        });

        if ($statusChanged) {
            $this->notifyUsers(
                $task,
                array_unique([
                    ...$task->assignees->pluck('id')->all(),
                    $task->created_by_id,
                    $task->project->owner_id,
                ]),
                $actor,
                'task_status_changed',
                'task_status_updates',
            );
        }

        return $task;
    }

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
