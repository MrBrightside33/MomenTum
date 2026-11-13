<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Metrics\ProjectMetrics;
use App\Support\Metrics\TaskMetrics;
use App\Support\Metrics\UserMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.overview');
    }

    public function data(
        Request $request,
        UserMetrics $userMetrics,
        TaskMetrics $taskMetrics,
        ProjectMetrics $projectMetrics,
    ): View {
        $timezone = $request->user()->settings['timezone'] ?? config('app.timezone');

        if (! in_array($timezone, array_keys(config('settings.timezones', [])), true)) {
            $timezone = config('app.timezone');
        }

        $localNow = now($timezone);
        $userCounts = $userMetrics->counts();
        $taskCounts = $taskMetrics->counts(['today' => $localNow->toDateString()]);
        $projectCounts = $projectMetrics->counts();
        $recentActivity = AuditLog::query()
            ->with('actor:id,name')
            ->latest('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'user_id', 'action', 'description', 'created_at']);

        return view('admin.partials.overview-data', [
            'userCounts' => $userCounts,
            'taskCounts' => $taskCounts,
            'projectCounts' => $projectCounts,
            'adminMemberCount' => $userCounts['members'],
            'failedSignInCount' => AuditLog::query()
                ->where('action', 'auth.login_failed')
                ->where('created_at', '>=', $localNow->copy()->subDay()->utc())
                ->count(),
            'recentActivity' => $recentActivity,
        ]);
    }
}
