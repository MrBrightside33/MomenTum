<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    $user = auth()->user();

    if ($user !== null && $user->defaultPageRoute() !== 'home') {
        return redirect()->route($user->defaultPageRoute());
    }

    return view('landing');
})->name('home');

$sampleTasks = fn (): array => [
    [
        'id' => 'sample-task-1',
        'title' => 'Finalize mobile app wireframes',
        'description' => 'Finalize the mobile app wireframes for onboarding, home, and task details. Incorporate the latest review feedback and prepare the Figma file for the product team’s handoff.',
        'priority' => 'High',
        'category' => 'Product Design',
        'status' => 'In Progress',
        'due' => 'Today',
        'due_date' => today()->toDateString(),
        'due_urgent' => true,
        'done' => false,
        'assignees' => ['AM', 'LK'],
        'assignee_ids' => ['am', 'lk'],
        'assignee_names' => ['Alex Morgan', 'Lina Kim'],
    ],
    [
        'id' => 'sample-task-2',
        'title' => 'Review API documentation',
        'description' => 'Review the latest API documentation and share feedback with the team.',
        'priority' => 'Medium',
        'category' => 'Development',
        'status' => 'In Progress',
        'due' => 'Today',
        'due_date' => today()->toDateString(),
        'due_urgent' => true,
        'done' => false,
        'assignees' => ['JD'],
        'assignee_ids' => ['jd'],
        'assignee_names' => ['Jordan Diaz'],
    ],
    [
        'id' => 'sample-task-3',
        'title' => 'Prepare weekly progress report',
        'description' => 'Summarize this week’s progress, blockers, and next steps.',
        'priority' => 'Low',
        'category' => 'Operations',
        'status' => 'Open',
        'due' => 'Tomorrow',
        'due_date' => today()->addDay()->toDateString(),
        'due_urgent' => false,
        'done' => false,
        'assignees' => ['AM'],
        'assignee_ids' => ['am'],
        'assignee_names' => ['Alex Morgan'],
    ],
    [
        'id' => 'sample-task-4',
        'title' => 'Update onboarding checklist',
        'description' => 'Update the onboarding checklist with the latest product changes.',
        'priority' => 'Medium',
        'category' => 'Product Design',
        'status' => 'Completed',
        'due' => 'Sep 28',
        'due_date' => '2026-09-28',
        'due_urgent' => false,
        'done' => true,
        'assignees' => ['LK', 'JD'],
        'assignee_ids' => ['lk', 'jd'],
        'assignee_names' => ['Lina Kim', 'Jordan Diaz'],
    ],
];

Route::get('/dashboard', function () use ($sampleTasks) {
    // Static sample data for now. Later this comes from the database.
    $user = ['name' => 'Alex'];

    $stats = [
        ['key' => 'total', 'label' => 'Total tasks', 'value' => '36', 'note' => '12 open + 24 completed', 'icon' => 'list', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-600'],
        ['key' => 'in_progress', 'label' => 'In progress', 'value' => '5', 'note' => '2 due today', 'icon' => 'clock', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
        ['key' => 'completed', 'label' => 'Completed', 'value' => '24', 'note' => '24 completed', 'icon' => 'check', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600'],
        ['key' => 'productivity', 'label' => 'Productivity', 'value' => '86%', 'note' => "You're on a great streak", 'icon' => 'chart-line', 'bg' => 'bg-violet-50', 'fg' => 'text-violet-600'],
    ];

    $tasks = $sampleTasks();

    // Bar heights are percentages of the chart height
    $week = [
        ['day' => 'M', 'value' => 45],
        ['day' => 'T', 'value' => 68],
        ['day' => 'W', 'value' => 50],
        ['day' => 'T', 'value' => 82, 'highlight' => true],
        ['day' => 'F', 'value' => 64],
        ['day' => 'S', 'value' => 35],
        ['day' => 'S', 'value' => 24],
    ];

    return view('dashboard', compact('user', 'stats', 'tasks', 'week'));
})->name('dashboard');

Route::get('/calendar', function () use ($sampleTasks) {
    $today = today();
    $calendarTasks = collect($sampleTasks())->map(function (array $task) use ($today): array {
        $dueDate = Carbon::parse($task['due_date'])->startOfDay();
        $daysUntil = (int) $today->copy()->startOfDay()->diffInDays($dueDate, false);
        $dueLabel = match (true) {
            $daysUntil < 0 => 'Overdue by '.abs($daysUntil).' day'.(abs($daysUntil) === 1 ? '' : 's'),
            $daysUntil === 0 => 'Due today',
            $daysUntil === 1 => 'Due tomorrow',
            default => 'Due in '.$daysUntil.' days',
        };

        return [
            'id' => $task['id'],
            'title' => $task['title'],
            'date' => $dueDate->toDateString(),
            'priority' => $task['priority'],
            'category' => $task['category'],
            'completed' => $task['done'],
            'days_until' => $daysUntil,
            'due_label' => $dueLabel,
            'url' => route('dashboard').'#task-row-'.$task['id'],
        ];
    });
    $deadlineAlerts = $calendarTasks
        ->filter(fn (array $task): bool => ! $task['completed'] && $task['days_until'] <= 1)
        ->values();
    $upcomingDeadlines = $calendarTasks
        ->filter(fn (array $task): bool => ! $task['completed'] && $task['days_until'] >= 0 && $task['days_until'] <= 7)
        ->values();
    $dueTodayCount = $calendarTasks
        ->filter(fn (array $task): bool => ! $task['completed'] && $task['days_until'] === 0)
        ->count();
    $dueTomorrowCount = $calendarTasks
        ->filter(fn (array $task): bool => ! $task['completed'] && $task['days_until'] === 1)
        ->count();
    $overdueCount = $calendarTasks
        ->filter(fn (array $task): bool => ! $task['completed'] && $task['days_until'] < 0)
        ->count();

    return view('calendar', [
        'calendarTasks' => $calendarTasks->values(),
        'deadlineAlerts' => $deadlineAlerts,
        'upcomingDeadlines' => $upcomingDeadlines,
        'dueTodayCount' => $dueTodayCount,
        'dueTomorrowCount' => $dueTomorrowCount,
        'overdueCount' => $overdueCount,
        'today' => $today->toDateString(),
        'todayLabel' => $today->format('l, F j, Y'),
        'monthLabel' => $today->format('F Y'),
    ]);
})->name('calendar');

Route::get('/projects', function () use ($sampleTasks) {
    $allTasks = collect($sampleTasks());
    $teamMembers = collect(config('tasks.team'))->keyBy('id');
    $projectDescriptions = [
        'Personal' => 'Keep individual goals and focused work in one place.',
        'Product Design' => 'Research, plan, and refine thoughtful product experiences.',
        'Development' => 'Build and maintain reliable product experiences.',
        'Operations' => 'Keep team workflows and weekly planning on track.',
        'Marketing' => 'Coordinate campaigns, content, and launch-ready work.',
    ];

    $projects = collect(config('tasks.projects'))
        ->map(function (string $name) use ($allTasks, $teamMembers, $projectDescriptions): array {
            $projectTasks = $allTasks->where('category', $name)->values();
            $completedTaskCount = $projectTasks->filter(fn (array $task): bool => $task['done'])->count();
            $openTasks = $projectTasks->filter(fn (array $task): bool => ! $task['done']);
            $status = match (true) {
                $projectTasks->isEmpty() => 'planning',
                $openTasks->isEmpty() => 'completed',
                default => 'in_progress',
            };
            $members = $projectTasks
                ->flatMap(fn (array $task): array => $task['assignee_ids'] ?? [])
                ->unique()
                ->map(fn (string $id): ?array => $teamMembers->get($id))
                ->filter()
                ->values();
            $projectTaskData = $projectTasks
                ->sortBy('due_date')
                ->map(fn (array $task): array => [
                    'id' => $task['id'],
                    'title' => $task['title'],
                    'due' => $task['due'],
                    'done' => $task['done'],
                    'url' => route('dashboard').'#task-row-'.$task['id'],
                ])
                ->values()
                ->all();

            return [
                'name' => $name,
                'description' => $projectDescriptions[$name],
                'status' => $status,
                'task_count' => $projectTasks->count(),
                'completed_task_count' => $completedTaskCount,
                'progress' => $projectTasks->isEmpty()
                    ? 0
                    : (int) round(($completedTaskCount / $projectTasks->count()) * 100),
                'next_deadline' => $openTasks->sortBy('due_date')->first()['due'] ?? null,
                'members' => $members->all(),
                'tasks' => $projectTaskData,
                'search' => $name.' '.$projectDescriptions[$name].' '.$projectTasks->pluck('title')->implode(' '),
            ];
        })
        ->values();

    $today = today();
    $taskCount = $projects->sum('task_count');
    $completedTaskCount = $projects->sum('completed_task_count');
    $projectCounts = [
        'all' => $projects->count(),
        'in_progress' => $projects->where('status', 'in_progress')->count(),
        'completed' => $projects->where('status', 'completed')->count(),
        'planning' => $projects->where('status', 'planning')->count(),
    ];
    $projectStats = [
        ['label' => 'Total projects', 'value' => $projects->count(), 'note' => 'Across your workspace', 'icon' => 'folder', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-600'],
        ['label' => 'In progress', 'value' => $projectCounts['in_progress'], 'note' => 'Projects with open tasks', 'icon' => 'clock', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
        ['label' => 'Tasks completed', 'value' => $completedTaskCount, 'note' => 'of '.$taskCount.' tasks tracked', 'icon' => 'check', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600'],
        ['label' => 'Planning', 'value' => $projectCounts['planning'], 'note' => 'Ready when you are', 'icon' => 'sparkles', 'bg' => 'bg-violet-50', 'fg' => 'text-violet-600'],
    ];

    return view('projects', compact('projects', 'projectCounts', 'projectStats', 'today'));
})->name('projects');

Route::get('/team', function () use ($sampleTasks) {
    $tasks = collect($sampleTasks());
    $teamMembers = collect(config('tasks.team'))
        ->map(function (array $member) use ($tasks): array {
            $assignedTasks = $tasks
                ->filter(fn (array $task): bool => in_array($member['id'], $task['assignee_ids'] ?? [], true))
                ->values();
            $projects = $assignedTasks->pluck('category')->unique()->values()->all();
            $role = $member['id'] === 'am' ? 'Owner' : 'Member';

            return [
                ...$member,
                'role' => $role,
                'task_count' => $assignedTasks->count(),
                'completed_task_count' => $assignedTasks->where('done', true)->count(),
                'projects' => $projects,
                'search' => $member['name'].' '.$role.' '.implode(' ', $projects),
            ];
        })
        ->values();
    $teamCounts = [
        'all' => $teamMembers->count(),
        'owner' => $teamMembers->where('role', 'Owner')->count(),
        'member' => $teamMembers->where('role', 'Member')->count(),
    ];
    $teamStats = [
        ['label' => 'Team members', 'value' => $teamMembers->count(), 'note' => 'In your workspace', 'icon' => 'users', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-600'],
        ['label' => 'Projects', 'value' => count(config('tasks.projects')), 'note' => 'Available to the team', 'icon' => 'folder', 'bg' => 'bg-violet-50', 'fg' => 'text-violet-600'],
        ['label' => 'Tasks assigned', 'value' => $tasks->filter(fn (array $task): bool => ! empty($task['assignee_ids']))->count(), 'note' => 'With at least one teammate', 'icon' => 'list', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
        ['label' => 'Tasks completed', 'value' => $tasks->where('done', true)->count(), 'note' => 'Across your workspace', 'icon' => 'check', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600'],
    ];

    return view('team', compact('teamMembers', 'teamCounts', 'teamStats'));
})->name('team');

Route::get('/inbox', function () {
    // Static sample notifications until inbox activity is backed by the database.
    $inboxGroups = [
        'Today' => [
            [
                'id' => 'notification-1',
                'actor' => 'Lina Kim',
                'headline' => 'Lina Kim assigned you a task',
                'detail' => 'Finalize mobile app wireframes · Product Design',
                'initials' => 'LK',
                'avatar' => 'bg-violet-100 text-violet-700',
                'icon' => 'users',
                'icon_color' => 'bg-indigo-50 text-indigo-600',
                'time' => '10 min ago',
                'read' => false,
                'task_id' => 'sample-task-1',
            ],
            [
                'id' => 'notification-2',
                'actor' => 'MomenTum',
                'headline' => 'A task is due today',
                'detail' => 'Review API documentation · Development',
                'initials' => 'MT',
                'avatar' => 'bg-indigo-100 text-indigo-700',
                'icon' => 'clock',
                'icon_color' => 'bg-amber-50 text-amber-600',
                'time' => '1 hour ago',
                'read' => false,
                'task_id' => 'sample-task-2',
            ],
            [
                'id' => 'notification-3',
                'actor' => 'Jordan Diaz',
                'headline' => 'Jordan Diaz mentioned you in a comment',
                'detail' => '“Could you review the updated checklist before tomorrow?”',
                'initials' => 'JD',
                'avatar' => 'bg-emerald-100 text-emerald-700',
                'icon' => 'inbox',
                'icon_color' => 'bg-sky-50 text-sky-600',
                'time' => '2 hours ago',
                'read' => false,
                'task_id' => 'sample-task-3',
            ],
            [
                'id' => 'notification-4',
                'actor' => 'Lina Kim',
                'headline' => 'Lina Kim shared a project update',
                'detail' => 'The Product Design team added a new milestone.',
                'initials' => 'LK',
                'avatar' => 'bg-violet-100 text-violet-700',
                'icon' => 'folder',
                'icon_color' => 'bg-violet-50 text-violet-600',
                'time' => '4 hours ago',
                'read' => false,
                'task_id' => null,
            ],
        ],
        'Yesterday' => [
            [
                'id' => 'notification-5',
                'actor' => 'Jordan Diaz',
                'headline' => 'Jordan Diaz completed a task',
                'detail' => 'Update onboarding checklist · Product Design',
                'initials' => 'JD',
                'avatar' => 'bg-emerald-100 text-emerald-700',
                'icon' => 'check',
                'icon_color' => 'bg-emerald-50 text-emerald-600',
                'time' => 'Yesterday',
                'read' => true,
                'task_id' => 'sample-task-4',
            ],
            [
                'id' => 'notification-6',
                'actor' => 'MomenTum',
                'headline' => 'Your weekly summary is ready',
                'detail' => 'You completed 24 tasks and kept your 7-day streak going.',
                'initials' => 'MT',
                'avatar' => 'bg-indigo-100 text-indigo-700',
                'icon' => 'chart-line',
                'icon_color' => 'bg-indigo-50 text-indigo-600',
                'time' => 'Yesterday',
                'read' => true,
                'task_id' => null,
            ],
        ],
    ];
    $notifications = collect($inboxGroups)->flatten(1);
    $inboxCounts = [
        'all' => $notifications->count(),
        'unread' => $notifications->where('read', false)->count(),
        'archived' => 0,
    ];

    return view('inbox', compact('inboxGroups', 'inboxCounts'));
})->name('inbox');

Route::view('/login', 'auth.login')->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.submit');

Route::get('/register', [AuthController::class, 'register'])->name('register');

Route::post('/register', [AuthController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('register.submit');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'auth.session'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::put('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');
});

// Share the task rules and row renderer between the temporary create and edit endpoints.
$taskValidationRules = function (bool $allowPastDueDate = false): array {
    return [
        'title' => ['required', 'string', 'min:3', 'max:120'],
        'description' => ['nullable', 'string', 'max:500'],
        'project' => ['required', Rule::in(config('tasks.projects'))],
        'priority' => ['required', Rule::in(config('tasks.priorities'))],
        'status' => ['required', Rule::in(config('tasks.statuses'))],
        'due_date' => $allowPastDueDate
            ? ['required', 'date']
            : ['required', 'date', 'after_or_equal:today'],
        'assignees' => ['nullable', 'array'],
        'assignees.*' => [Rule::in(collect(config('tasks.team'))->pluck('id')->all())],
    ];
};

$renderTaskRow = function (string $taskId, array $validated, string $message, int $statusCode): JsonResponse {
    $dueDate = Carbon::parse($validated['due_date']);
    $today = today();
    $dueLabel = match (true) {
        $dueDate->isSameDay($today) => 'Today',
        $dueDate->isSameDay($today->copy()->addDay()) => 'Tomorrow',
        default => $dueDate->format('M j'),
    };
    $team = collect(config('tasks.team'))->keyBy('id');
    $assigneeIds = $validated['assignees'] ?? [];
    $assignedMembers = collect($assigneeIds)->map(fn (string $id): array => $team->get($id));
    $task = [
        'id' => $taskId,
        'title' => $validated['title'],
        'description' => $validated['description'] ?? '',
        'priority' => $validated['priority'],
        'category' => $validated['project'],
        'status' => $validated['status'],
        'due' => $dueLabel,
        'due_date' => $dueDate->toDateString(),
        'due_urgent' => $dueDate->isSameDay($today),
        'done' => $validated['status'] === 'Completed',
        'assignee_ids' => $assigneeIds,
        'assignee_names' => $assignedMembers->pluck('name')->all(),
        'assignees' => $assignedMembers->pluck('initials')->all(),
    ];

    // TEMPORARY: not saved yet. Replace with Task::create() when the database layer is built.
    return response()->json([
        'message' => $message,
        'task_id' => $taskId,
        'html' => view('tasks._row', ['task' => $task])->render(),
    ], $statusCode);
};

Route::post('/tasks', function (Request $request) use ($renderTaskRow, $taskValidationRules) {
    return $renderTaskRow(
        (string) Str::uuid(),
        $request->validate($taskValidationRules()),
        'Task created',
        201,
    );
})->name('tasks.store');

Route::patch('/tasks/{taskId}', function (Request $request, string $taskId) use ($renderTaskRow, $taskValidationRules) {
    return $renderTaskRow(
        $taskId,
        $request->validate($taskValidationRules(allowPastDueDate: true)),
        'Task updated',
        200,
    );
})->where('taskId', '[A-Za-z0-9-]+')->name('tasks.update');

Route::delete('/tasks/{taskId}', function (string $taskId) {
    // TEMPORARY: the task list is client-side only until task persistence is added.
    return response()->json([
        'message' => 'Task deleted',
        'task_id' => $taskId,
    ]);
})->where('taskId', '[A-Za-z0-9-]+')->name('tasks.destroy');
