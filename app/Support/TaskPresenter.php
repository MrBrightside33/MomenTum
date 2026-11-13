<?php

namespace App\Support;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Support\Carbon;

class TaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Task $task, ?User $viewer = null, bool $readOnly = false): array
    {
        $task->loadMissing([
            'project:id,name,owner_id',
            'assignees:id,name',
            'creator:id,name',
            'progressUpdater:id,name',
            'activities.user:id,name',
            'comments.user:id,name',
        ]);
        $dueDate = Carbon::parse($task->due_date)->startOfDay();
        $today = today()->startOfDay();
        $isCompleted = $task->status === TaskStatus::Completed;
        $isOverdue = ! $isCompleted && $dueDate->lessThan($today);
        $assignees = $task->assignees->values();
        $creator = $task->creator;
        $people = collect([$creator])
            ->merge($assignees)
            ->filter()
            ->unique(fn (User $person): int|string => $person->getKey())
            ->map(fn (User $person): array => [
                'id' => $person->getKey(),
                'name' => $person->name,
                'initials' => self::initials($person->name),
                'role' => $person->is($creator) ? 'owner' : 'collaborator',
                'is_me' => $viewer !== null && $person->is($viewer),
            ])
            ->values();
        $progress = (int) $task->progress;
        $progressLabel = self::stageLabel($progress);
        $statusLabel = match ($task->status) {
            TaskStatus::Open => 'Planning',
            TaskStatus::InProgress => 'In progress',
            TaskStatus::Completed => 'Completed',
        };
        $isAdmin = $viewer?->isAdmin() ?? false;
        $isManager = $viewer?->isManager() ?? false;
        $isCreator = $viewer !== null && $task->created_by_id === $viewer->getKey();
        $isProjectOwner = $viewer !== null && $task->project->owner_id === $viewer->getKey();
        $isAssignee = $viewer !== null && $assignees->contains(fn (User $assignee): bool => $assignee->is($viewer));
        $canView = $viewer !== null
            && $viewer->is_active
            && ($isAdmin || $isManager || $isProjectOwner || $isCreator || $isAssignee);
        $canUpdate = ! $readOnly && $viewer !== null && ! $isAdmin && ($isManager || $isCreator);
        $canChangeStatus = ! $readOnly && $viewer !== null && ! $isAdmin
            && ($isManager || $isCreator || $isProjectOwner || $isAssignee);
        $canComment = $canChangeStatus;

        return [
            'id' => $task->getKey(),
            'urls' => [
                'show' => route('tasks.show', ['task' => $task->getKey()]),
                'progress' => route('tasks.progress', ['task' => $task->getKey()]),
                'status' => route('tasks.status.update', ['task' => $task->getKey()]),
                'comments' => route('tasks.comments.store', ['task' => $task->getKey()]),
                'project' => route('projects.show', ['task' => $task->getKey()]),
            ],
            'title' => $task->title,
            'description' => $task->description ?? '',
            'priority' => $task->priority->value,
            'project_id' => $task->project_id,
            'category' => $task->project->name,
            'project_name' => $task->project->name,
            'status' => $task->status->value,
            'status_label' => $statusLabel,
            'progress' => $progress,
            'progress_label' => $progressLabel,
            'progress_updated_at' => $task->progress_updated_at?->toIso8601String(),
            'progress_updated_by' => $task->progressUpdater?->name,
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'creator' => [
                'id' => $creator?->getKey(),
                'name' => $creator?->name ?? 'Unknown',
                'initials' => self::initials($creator?->name ?? ''),
            ],
            'people' => $people->all(),
            'due' => match (true) {
                $dueDate->isSameDay($today) => 'Today',
                $dueDate->isSameDay($today->copy()->addDay()) => 'Tomorrow',
                default => $dueDate->format('M j'),
            },
            'due_date' => $dueDate->toDateString(),
            'due_overdue' => $isOverdue,
            'due_urgent' => ! $isCompleted && $dueDate->lessThanOrEqualTo($today),
            'done' => $isCompleted,
            'activities' => self::activityEntries($task),
            'assignee_ids' => $assignees->pluck('id')->all(),
            'assignee_names' => $assignees->pluck('name')->all(),
            'assignees' => $assignees->map(fn (User $assignee): string => self::initials($assignee->name))->all(),
            'can_update' => $canUpdate,
            'can_change_status' => $canChangeStatus,
            'can_comment' => $canComment,
            'can' => [
                'progress' => ! $readOnly && $viewer !== null && $viewer->can('updateProgress', $task),
                'view' => $canView,
                'edit' => $canUpdate,
                'change_status' => $canChangeStatus,
                'comment' => $canComment,
            ],
        ];
    }

    private static function stageLabel(int $progress): string
    {
        foreach (config('progress.stages', []) as $stage) {
            if ($stage['value'] === $progress) {
                return $stage['label'];
            }
        }

        return 'Unknown';
    }

    /**
     * @return array<int, array{id: int|string, kind: string, name: string, initials: string, text: string, relative_time: string, timestamp: string}>
     */
    private static function activityEntries(Task $task): array
    {
        $activities = $task->activities
            ->reject(fn (TaskActivity $activity): bool => $activity->event_type === 'commented')
            ->map(function (TaskActivity $activity): array {
                $metadata = $activity->metadata ?? [];
                $userName = $activity->user?->name ?? 'Someone';
                $kind = isset($metadata['from_progress'], $metadata['to_progress'])
                    ? 'progress'
                    : 'activity';

                return [
                    'id' => $activity->getKey(),
                    'kind' => $kind,
                    'name' => $userName,
                    'initials' => self::initials($userName),
                    'text' => $metadata['message'] ?? match ($activity->event_type) {
                        'created' => 'created this task.',
                        'status_changed' => 'changed the task status.',
                        default => 'updated this task.',
                    },
                    'relative_time' => $activity->created_at->diffForHumans(),
                    'timestamp' => $activity->created_at->toIso8601String(),
                ];
            });

        $comments = $task->comments->map(function (TaskComment $comment): array {
            $userName = $comment->user?->name ?? 'Someone';

            return [
                'id' => $comment->getKey(),
                'kind' => 'comment',
                'name' => $userName,
                'initials' => self::initials($userName),
                'text' => 'commented: “'.$comment->body.'”',
                'relative_time' => $comment->created_at->diffForHumans(),
                'timestamp' => $comment->created_at->toIso8601String(),
            ];
        });

        return $activities
            ->concat($comments)
            ->sortBy('timestamp')
            ->values()
            ->all();
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        return mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
    }
}
