<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Support\TaskPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, TaskPresenter $presenter): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.overview');
        }

        $tasks = Task::query()
            ->visibleTo($user)
            ->with(['project:id,name', 'assignees:id,name'])
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->get();

        $totalTasks = $tasks->count();
        $inProgressTasks = $tasks->where('status', TaskStatus::InProgress)->count();
        $completedTasks = $tasks->where('status', TaskStatus::Completed)->count();
        $completedThisWeek = TaskActivity::query()
            ->where('user_id', $user->getKey())
            ->where('to_status', TaskStatus::Completed->value)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $weekStart = now()->startOfWeek();
        $weekActivities = TaskActivity::query()
            ->where('user_id', $user->getKey())
            ->where('to_status', TaskStatus::Completed->value)
            ->whereBetween('created_at', [$weekStart, $weekStart->copy()->endOfWeek()])
            ->get(['created_at'])
            ->groupBy(fn (TaskActivity $activity): string => $activity->created_at->toDateString());
        $maximumDailyCount = max(1, $weekActivities->map->count()->max() ?? 0);

        $week = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $weekActivities, $maximumDailyCount): array {
            $date = $weekStart->copy()->addDays($offset);
            $count = $weekActivities->get($date->toDateString(), collect())->count();

            return [
                'day' => mb_substr($date->format('D'), 0, 1),
                'value' => $count === 0 ? 8 : max(8, (int) round(($count / $maximumDailyCount) * 100)),
                'count' => $count,
                'highlight' => $date->isToday(),
            ];
        });

        $activityDates = TaskActivity::query()
            ->where('user_id', $user->getKey())
            ->where('to_status', TaskStatus::Completed->value)
            ->whereBetween('created_at', [today()->subDays(30), now()])
            ->get(['created_at'])
            ->map(fn (TaskActivity $activity): string => $activity->created_at->toDateString())
            ->unique()
            ->flip();
        $streakCursor = $activityDates->has(today()->toDateString())
            ? today()
            : today()->subDay();
        $streakDays = 0;

        while ($streakCursor !== null && $activityDates->has($streakCursor->toDateString())) {
            $streakDays++;
            $streakCursor->subDay();
        }

        $stats = [
            [
                'key' => 'total',
                'label' => 'Total tasks',
                'value' => $totalTasks,
                'note' => $tasks->where('status', TaskStatus::Open)->count().' open',
                'icon' => 'list',
                'bg' => 'bg-indigo-50',
                'fg' => 'text-indigo-600',
            ],
            [
                'key' => 'in_progress',
                'label' => 'In progress',
                'value' => $inProgressTasks,
                'note' => $tasks->filter(fn (Task $task): bool => $task->status !== TaskStatus::Completed && $task->due_date->isToday())->count().' due today',
                'icon' => 'clock',
                'bg' => 'bg-amber-50',
                'fg' => 'text-amber-600',
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'value' => $completedTasks,
                'note' => $completedTasks.' completed',
                'icon' => 'check',
                'bg' => 'bg-emerald-50',
                'fg' => 'text-emerald-600',
            ],
            [
                'key' => 'productivity',
                'label' => 'Productivity',
                'value' => ($totalTasks === 0 ? 0 : (int) round(($completedTasks / $totalTasks) * 100)).'%',
                'note' => $completedTasks.' of '.$totalTasks.' tasks completed',
                'icon' => 'chart-line',
                'bg' => 'bg-violet-50',
                'fg' => 'text-violet-600',
            ],
        ];

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            'tasks' => $tasks->map(fn (Task $task): array => $presenter->present($task, $user)),
            'week' => $week,
            'weekTitle' => $weekStart->format('M j').' – '.$weekStart->copy()->endOfWeek()->format('M j'),
            'completedThisWeek' => $completedThisWeek,
            'streakDays' => $streakDays,
            'streakProgress' => min(100, (int) round(($streakDays / 7) * 100)),
            'streakTitle' => $streakDays > 0 ? "You’re on a {$streakDays}-day streak" : 'Start a new streak today',
            'streakCopy' => $streakDays > 0
                ? 'Keep the momentum going. Complete one more task today.'
                : 'Complete a task today to begin building your streak.',
        ]);
    }
}
