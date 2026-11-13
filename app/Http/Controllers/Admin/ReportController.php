<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Metrics\ProjectMetrics;
use App\Support\Metrics\TaskMetrics;
use App\Support\Metrics\UserMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('admin.reports.index');
    }

    public function data(
        Request $request,
        TaskMetrics $taskMetrics,
        ProjectMetrics $projectMetrics,
        UserMetrics $userMetrics,
    ): View {
        $taskCounts = $taskMetrics->counts();
        $projectCounts = $projectMetrics->counts();
        $userCounts = $userMetrics->counts();
        $timezone = $request->user()->settings['timezone'] ?? config('app.timezone');

        if (! in_array($timezone, array_keys(config('settings.timezones', [])), true)) {
            $timezone = config('app.timezone');
        }

        return view('admin.reports.partials.data', [
            'taskCounts' => $taskCounts,
            'projectCounts' => $projectCounts,
            'userCounts' => $userCounts,
            'taskStatuses' => TaskStatus::cases(),
            'projectStatuses' => ProjectStatus::cases(),
            'recentAuditCount' => AuditLog::query()
                ->where('created_at', '>=', now($timezone)->subDays(7)->utc())
                ->count(),
        ]);
    }
}
