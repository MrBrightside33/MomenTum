<?php

namespace App\Support\Metrics;

use App\Models\Task;
use App\Models\TaskActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TaskMetrics
{
    /**
     * @param  array{user_id?: int, project_id?: int, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string, today?: string, priority?: string}  $scope
     * @return array{total: int, completed: int, open: int, in_progress: int, overdue: int, due_today: int, completion_rate: int, created: int, assigned_open: int, statuses: array<string, int>}
     */
    public function counts(array $scope = []): array
    {
        $completedStatus = config('metrics.tasks.completed_status');
        $inProgressStatus = config('metrics.tasks.in_progress_status');
        $openStatus = config('metrics.tasks.open_status');
        $today = $scope['today'] ?? now()->toDateString();
        $tomorrow = Carbon::parse($today)->addDay()->toDateString();
        $userId = $scope['user_id'] ?? null;
        $query = $this->query($scope);
        $aggregateQuery = (clone $query)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS completed', [$completedStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_open', [$openStatus])
            ->selectRaw('SUM(CASE WHEN status != ? THEN 1 ELSE 0 END) AS open', [$completedStatus])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS in_progress', [$inProgressStatus])
            ->selectRaw(
                'SUM(CASE WHEN DATE(due_date) < ? AND status != ? THEN 1 ELSE 0 END) AS overdue',
                [$today, $completedStatus],
            )
            ->selectRaw(
                'SUM(CASE WHEN due_date >= ? AND due_date < ? AND status != ? THEN 1 ELSE 0 END) AS due_today',
                [$today, $tomorrow, $completedStatus],
            );

        if ($userId === null) {
            $aggregateQuery
                ->selectRaw('0 AS created')
                ->selectRaw('0 AS assigned_open');
        } else {
            $aggregateQuery
                ->selectRaw('SUM(CASE WHEN created_by_id = ? THEN 1 ELSE 0 END) AS created', [$userId])
                ->selectRaw(
                    'SUM(CASE WHEN status != ? AND EXISTS (SELECT 1 FROM task_assignees WHERE task_assignees.task_id = tasks.id AND task_assignees.user_id = ?) THEN 1 ELSE 0 END) AS assigned_open',
                    [$completedStatus, $userId],
                );
        }

        $aggregate = $aggregateQuery->toBase()->first();
        $total = (int) $aggregate->total;
        $completed = (int) $aggregate->completed;

        return [
            'total' => $total,
            'completed' => $completed,
            'open' => (int) $aggregate->open,
            'in_progress' => (int) $aggregate->in_progress,
            'overdue' => (int) $aggregate->overdue,
            'due_today' => (int) $aggregate->due_today,
            'completion_rate' => $total === 0 ? 0 : (int) round(($completed / $total) * 100),
            'created' => (int) $aggregate->created,
            'assigned_open' => (int) $aggregate->assigned_open,
            'statuses' => [
                $openStatus => (int) $aggregate->status_open,
                $inProgressStatus => (int) $aggregate->in_progress,
                $completedStatus => $completed,
            ],
        ];
    }

    /**
     * @param  array<int, int>  $userIds
     * @param  array{project_id?: int, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     * @return array<int, int>
     */
    public function assignedCounts(array $userIds, array $scope = []): array
    {
        if ($userIds === []) {
            return [];
        }

        $query = Task::query()
            ->join('task_assignees', 'tasks.id', '=', 'task_assignees.task_id')
            ->whereIn('task_assignees.user_id', $userIds);

        if (isset($scope['project_id'])) {
            $query->where('tasks.project_id', $scope['project_id']);
        }

        if (isset($scope['from'])) {
            $query->where('tasks.created_at', '>=', $scope['from']);
        }

        if (isset($scope['to'])) {
            $query->where('tasks.created_at', '<=', $scope['to']);
        }

        return $query
            ->select('task_assignees.user_id')
            ->selectRaw('COUNT(*) AS assigned_count')
            ->groupBy('task_assignees.user_id')
            ->pluck('assigned_count', 'task_assignees.user_id')
            ->map(static fn (int|string $count): int => (int) $count)
            ->all();
    }

    /**
     * @param  array{user_id?: int, project_id?: int, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     * @return array{completed: int}
     */
    public function completedActivity(array $scope = []): array
    {
        $query = TaskActivity::query()
            ->where('to_status', config('metrics.tasks.completed_status'));

        if (isset($scope['user_id'])) {
            $query->where('user_id', $scope['user_id']);
        }

        if (isset($scope['project_id'])) {
            $query->whereHas('task', fn (Builder $tasks): Builder => $tasks->where('project_id', $scope['project_id']));
        }

        if (isset($scope['from'])) {
            $query->where('created_at', '>=', $scope['from']);
        }

        if (isset($scope['to'])) {
            $query->where('created_at', '<=', $scope['to']);
        }

        return ['completed' => (int) $query->count()];
    }

    /**
     * @param  array{user_id?: int, project_id?: int, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string, today?: string, priority?: string}  $scope
     */
    private function query(array $scope): Builder
    {
        $query = Task::query();

        if (isset($scope['user_id'])) {
            $userId = $scope['user_id'];
            $query->where(function (Builder $query) use ($userId): void {
                $query->where('created_by_id', $userId)
                    ->orWhereHas('assignees', fn (Builder $assignees): Builder => $assignees->whereKey($userId))
                    ->orWhereHas('project', fn (Builder $projects): Builder => $projects->where('owner_id', $userId));
            });
        }

        if (isset($scope['project_id'])) {
            $query->where('project_id', $scope['project_id']);
        }

        if (isset($scope['from'])) {
            $query->where('created_at', '>=', $scope['from']);
        }

        if (isset($scope['to'])) {
            $query->where('created_at', '<=', $scope['to']);
        }

        if (isset($scope['priority'])) {
            $query->where('priority', $scope['priority']);
        }

        return $query;
    }
}
