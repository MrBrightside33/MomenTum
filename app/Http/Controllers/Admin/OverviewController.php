<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\View\View;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        $userCounts = collect(['active', 'pending', 'inactive'])
            ->mapWithKeys(fn (string $status): array => [
                $status => User::query()->accountStatus($status)->count(),
            ]);
        $taskCounts = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => Task::query()->where('status', $status->value)->count(),
            ]);
        $projectCounts = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status): array => [
                $status->value => Project::query()->effectiveStatus($status)->count(),
            ]);
        $recentActivity = AuditLog::query()
            ->with('actor:id,name')
            ->latest('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'user_id', 'action', 'description', 'created_at']);

        return view('admin.overview', [
            'userCounts' => $userCounts,
            'taskCounts' => $taskCounts,
            'projectCounts' => $projectCounts,
            'recentActivity' => $recentActivity,
        ]);
    }
}
