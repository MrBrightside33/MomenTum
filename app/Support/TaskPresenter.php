<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

class TaskPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(Task $task, ?User $viewer = null, bool $readOnly = false): array
    {
        $task->loadMissing(['project', 'assignees']);
        $dueDate = Carbon::parse($task->due_date)->startOfDay();
        $today = today()->startOfDay();
        $isCompleted = $task->status->value === 'Completed';
        $assignees = $task->assignees->values();

        return [
            'id' => $task->getKey(),
            'title' => $task->title,
            'description' => $task->description ?? '',
            'priority' => $task->priority->value,
            'project_id' => $task->project_id,
            'category' => $task->project->name,
            'status' => $task->status->value,
            'due' => match (true) {
                $dueDate->isSameDay($today) => 'Today',
                $dueDate->isSameDay($today->copy()->addDay()) => 'Tomorrow',
                default => $dueDate->format('M j'),
            },
            'due_date' => $dueDate->toDateString(),
            'due_urgent' => ! $isCompleted && $dueDate->lessThanOrEqualTo($today),
            'done' => $isCompleted,
            'assignee_ids' => $assignees->pluck('id')->all(),
            'assignee_names' => $assignees->pluck('name')->all(),
            'assignees' => $assignees->map(fn (User $assignee): string => self::initials($assignee->name))->all(),
            'can_update' => ! $readOnly && $viewer !== null && ! $viewer->isAdmin() && (
                $viewer->isManager() || $task->created_by_id === $viewer->getKey()
            ),
            'can_change_status' => ! $readOnly && $viewer !== null && ! $viewer->isAdmin() && (
                $viewer->isManager()
                || $task->created_by_id === $viewer->getKey()
                || $task->project->owner_id === $viewer->getKey()
                || $assignees->contains(fn (User $assignee): bool => $assignee->is($viewer))
            ),
            'can_comment' => ! $readOnly && $viewer !== null && ! $viewer->isAdmin() && (
                $viewer->isManager()
                || $task->created_by_id === $viewer->getKey()
                || $task->project->owner_id === $viewer->getKey()
                || $assignees->contains(fn (User $assignee): bool => $assignee->is($viewer))
            ),
        ];
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        return mb_strtoupper(mb_substr($first, 0, 1).mb_substr($last, 0, 1));
    }
}
