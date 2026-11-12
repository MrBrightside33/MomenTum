<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

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
                'url' => route('dashboard').' #task-row-'.$task['id'],
            ])
            ->values()->all();

        return [
            'name' => $name,
            'description' => $projectDescriptions[$name],
            'status' => $status,
            'task_count' => $projectTasks->count(),
            'completed_task_count' => $completedTaskCount,
            'progress' => $projectTasks->isEmpty() ? 0 : (int) round(($completedTaskCount / $projectTasks->count()) * 100),
            'next_deadline' => $openTasks->sortBy('due_date')->first()['due'] ?? null,
            'members' => $members->all(),
            'tasks' => $projectTaskData,
            'search' => $name.' '.$projectDescriptions[$name].' '.$projectTasks->pluck('title')->implode(' '),
        ];
    })
    ->values();

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

$html = view('projects', array_merge(compact('projects', 'projectCounts', 'projectStats'), ['today' => now()->toDateString()]))->render();
echo substr($html, 0, 2000);
