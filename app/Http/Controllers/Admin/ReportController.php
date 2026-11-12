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

class ReportController extends Controller
{
    public function index(): View
    {
        $taskCounts = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => Task::query()->where('status', $status->value)->count(),
            ]);
        $projectCounts = collect(ProjectStatus::cases())
            ->mapWithKeys(fn (ProjectStatus $status): array => [
                $status->value => Project::query()->effectiveStatus($status)->count(),
            ]);
        $userCounts = collect(['active', 'pending', 'inactive'])
            ->mapWithKeys(fn (string $status): array => [
                $status => User::query()->accountStatus($status)->count(),
            ]);

        return view('admin.reports.index', [
            'taskCounts' => $taskCounts,
            'projectCounts' => $projectCounts,
            'userCounts' => $userCounts,
            'taskStatuses' => TaskStatus::cases(),
            'projectStatuses' => ProjectStatus::cases(),
            'recentAuditCount' => AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }
}
