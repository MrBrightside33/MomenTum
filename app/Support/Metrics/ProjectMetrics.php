<?php

namespace App\Support\Metrics;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

class ProjectMetrics
{
    /**
     * @param  array{user_id?: int, project_id?: int, project_ids?: array<int, int>, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     * @return array{total: int, planning: int, in_progress: int, completed: int, archived: int}
     */
    public function counts(array $scope = []): array
    {
        $query = $this->query($scope);
        $archivedStatus = config('metrics.projects.archived_status');

        return [
            'total' => (int) (clone $query)->count(),
            'planning' => (int) (clone $query)->effectiveStatus(ProjectStatus::Planning)->count(),
            'in_progress' => (int) (clone $query)->effectiveStatus(ProjectStatus::InProgress)->count(),
            'completed' => (int) (clone $query)->effectiveStatus(ProjectStatus::Completed)->count(),
            'archived' => (int) (clone $query)->where('status', $archivedStatus)->count(),
        ];
    }

    /**
     * Return rounded average stored progress by project, excluding soft-deleted tasks.
     *
     * @param  array{user_id?: int, project_id?: int, project_ids?: array<int, int>, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     * @return array<int, int>
     */
    public function progress(array $scope = []): array
    {
        return array_map(
            static fn (array $summary): int => $summary['progress'],
            $this->summaries($scope),
        );
    }

    /**
     * @param  array{user_id?: int, project_id?: int, project_ids?: array<int, int>, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     * @return array<int, array{tasks: int, incomplete_tasks: int, completed_tasks: int, progress: int}>
     */
    public function summaries(array $scope = []): array
    {
        $query = Project::query()
            ->leftJoin('tasks', function (JoinClause $join) use ($scope): void {
                $join->on('tasks.project_id', '=', 'projects.id')
                    ->whereNull('tasks.deleted_at');

                if (isset($scope['from'])) {
                    $join->where('tasks.created_at', '>=', $scope['from']);
                }

                if (isset($scope['to'])) {
                    $join->where('tasks.created_at', '<=', $scope['to']);
                }
            })
            ->select('projects.id')
            ->selectRaw('COUNT(tasks.id) AS tasks')
            ->selectRaw(
                'SUM(CASE WHEN tasks.status != ? THEN 1 ELSE 0 END) AS incomplete_tasks',
                [config('metrics.tasks.completed_status')],
            )
            ->selectRaw(
                'SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS completed_tasks',
                [config('metrics.tasks.completed_status')],
            )
            ->selectRaw('COALESCE(ROUND(AVG(tasks.progress)), 0) AS progress')
            ->groupBy('projects.id');

        if (isset($scope['project_id'])) {
            $query->where('projects.id', $scope['project_id']);
        }

        if (isset($scope['project_ids'])) {
            $query->whereIn('projects.id', $scope['project_ids']);
        }

        if (isset($scope['user_id'])) {
            $userId = $scope['user_id'];
            $query->where(function (Builder $projects) use ($userId): void {
                $projects->where('projects.owner_id', $userId)
                    ->orWhereHas('tasks', fn (Builder $tasks): Builder => $tasks->where('created_by_id', $userId))
                    ->orWhereHas('tasks.assignees', fn (Builder $assignees): Builder => $assignees->whereKey($userId));
            });
        }

        return $query->toBase()->get()
            ->mapWithKeys(static fn (object $summary): array => [
                (int) $summary->id => [
                    'tasks' => (int) $summary->tasks,
                    'incomplete_tasks' => (int) $summary->incomplete_tasks,
                    'completed_tasks' => (int) $summary->completed_tasks,
                    'progress' => (int) $summary->progress,
                ],
            ])
            ->all();
    }

    /**
     * @param  array{user_id?: int, project_id?: int, project_ids?: array<int, int>, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string}  $scope
     */
    private function query(array $scope): Builder
    {
        $query = Project::query();

        if (isset($scope['user_id'])) {
            $userId = $scope['user_id'];
            $query->where(function (Builder $query) use ($userId): void {
                $query->where('owner_id', $userId)
                    ->orWhereHas('tasks', fn (Builder $tasks): Builder => $tasks->where('created_by_id', $userId))
                    ->orWhereHas('tasks.assignees', fn (Builder $assignees): Builder => $assignees->whereKey($userId));
            });
        }

        if (isset($scope['project_id'])) {
            $query->where('projects.id', $scope['project_id']);
        }

        if (isset($scope['project_ids'])) {
            $query->whereIn('projects.id', $scope['project_ids']);
        }
        if (isset($scope['from'])) {
            $query->where('projects.created_at', '>=', $scope['from']);
        }

        if (isset($scope['to'])) {
            $query->where('projects.created_at', '<=', $scope['to']);
        }

        return $query;
    }
}
