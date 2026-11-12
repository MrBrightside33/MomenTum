<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TaskPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request, TaskPresenter $presenter): View
    {
        Gate::authorize('viewAny', Project::class);
        $user = $request->user();
        $projects = Project::query()
            ->visibleTo($user)
            ->with([
                'owner:id,name',
                'tasks' => function (HasMany $query) use ($user): void {
                    $query->visibleTo($user)
                        ->with([
                            'assignees:id,name',
                            'creator:id,name',
                            'project:id,name,owner_id',
                        ])
                        ->orderBy('due_date')
                        ->orderBy('id');
                },
            ])
            ->orderBy('name')
            ->get()
            ->flatMap(function (Project $project) use ($user, $presenter): array {
                return $project->tasks
                    ->filter(fn (Task $task): bool => $task->priority === TaskPriority::High)
                    ->map(function (Task $task) use ($project, $user, $presenter): array {
                        $presented = $presenter->present($task, $user);
                        $members = collect([$project->owner, $task->creator])
                            ->merge($task->assignees)
                            ->filter()
                            ->unique('id')
                            ->map(fn (User $member): array => [
                                'id' => $member->getKey(),
                                'name' => $member->name,
                                'initials' => TaskPresenter::initials($member->name),
                            ])
                            ->values();
                        return [
                            'id' => $task->getKey(),
                            'name' => $task->title,
                            'status' => match ($task->status) {
                                TaskStatus::Open => ProjectStatus::Planning->value,
                                TaskStatus::InProgress => ProjectStatus::InProgress->value,
                                TaskStatus::Completed => ProjectStatus::Completed->value,
                            },
                            'progress' => match ($task->status) {
                                TaskStatus::Open => 0,
                                TaskStatus::InProgress => 50,
                                TaskStatus::Completed => 100,
                            },
                            'completed_task_count' => $task->status === TaskStatus::Completed ? 1 : 0,
                            'task_count' => 1,
                            'collaboration' => $members->count() > 1 ? 'Team project' : 'One-person project',
                            'members' => $members->all(),
                            'parent_project' => $project->name,
                            'due' => $presented['due'],
                            'task_url' => route('dashboard').'#task-row-'.$task->getKey(),
                            'search' => trim($task->title.' '.$task->description.' '.$project->name.' '.$members->pluck('name')->implode(' ')),
                        ];
                    })
                    ->all();
            })
            ->sortBy(fn (array $project): string => mb_strtolower($project['name'].' '.$project['parent_project']))
            ->values();

        $projectCounts = [
            'all' => $projects->count(),
            'in_progress' => $projects->where('status', ProjectStatus::InProgress->value)->count(),
            'completed' => $projects->where('status', ProjectStatus::Completed->value)->count(),
            'planning' => $projects->where('status', ProjectStatus::Planning->value)->count(),
        ];
        $totalTasks = $projects->count();
        $completedTasks = $projects->where('status', ProjectStatus::Completed->value)->count();
        $projectStats = [
            ['label' => 'High-priority projects', 'value' => $projectCounts['all'], 'note' => 'One project per high-priority task', 'icon' => 'folder', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-600'],
            ['label' => 'In progress', 'value' => $projectCounts['in_progress'], 'note' => 'High-priority projects underway', 'icon' => 'clock', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
            ['label' => 'Tasks completed', 'value' => $completedTasks, 'note' => 'of '.$totalTasks.' high-priority tasks completed', 'icon' => 'check', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600'],
            ['label' => 'Planning', 'value' => $projectCounts['planning'], 'note' => 'High-priority tasks not yet started', 'icon' => 'sparkles', 'bg' => 'bg-violet-50', 'fg' => 'text-violet-600'],
        ];

        return view('projects', [
            'projects' => $projects,
            'projectCounts' => $projectCounts,
            'projectStats' => $projectStats,
            'canCreateProjects' => $user->can('create', Project::class),
        ]);
    }

    public function store(StoreProjectRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($auditLogger, $request): void {
            $project = $request->user()->ownedProjects()->create($request->validated());
            $auditLogger->record('project.created', $project, 'Project created: '.$project->name, [], $request->user());
        });

        return redirect()->route('projects')->with('status', 'Project created');
    }

    public function archive(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('archive', $project);

        if ($project->status !== ProjectStatus::Archived) {
            DB::transaction(function () use ($auditLogger, $project, $request): void {
                $project->forceFill(['status' => ProjectStatus::Archived])->save();
                $auditLogger->record(
                    'project.archived',
                    $project,
                    'Project archived: '.$project->name,
                    [],
                    $request->user(),
                );
            });
        }

        return redirect()->route('projects')->with('status', 'Project archived');
    }
}
