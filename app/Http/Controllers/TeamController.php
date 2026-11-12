<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Task::class);
        $user = $request->user();
        $tasks = Task::query()
            ->visibleTo($user)
            ->with(['project.owner:id,name', 'assignees:id,name'])
            ->get();
        $ownedProjectIds = $user->ownedProjects()->pluck('id')->all();
        $members = collect([$user])
            ->merge($tasks->flatMap(fn (Task $task) => $task->assignees))
            ->merge($tasks->map(fn (Task $task) => $task->project->owner))
            ->filter()
            ->unique('id')
            ->values()
            ->map(function (User $member) use ($tasks, $ownedProjectIds): array {
                $assignedTasks = $tasks->filter(
                    fn (Task $task): bool => $task->assignees->contains(
                        fn (User $assignee): bool => $assignee->is($member),
                    ),
                );
                $projects = $assignedTasks->map(fn (Task $task): string => $task->project->name)->unique()->values();
                $role = in_array($member->getKey(), $ownedProjectIds, true) ? 'Owner' : 'Member';

                return [
                    'id' => $member->getKey(),
                    'name' => $member->name,
                    'email' => $member->email,
                    'initials' => TaskPresenter::initials($member->name),
                    'role' => $role,
                    'task_count' => $assignedTasks->count(),
                    'completed_task_count' => $assignedTasks->filter(
                        fn (Task $task): bool => $task->status === TaskStatus::Completed,
                    )->count(),
                    'projects' => $projects->all(),
                    'search' => trim($member->name.' '.$role.' '.$projects->implode(' ')),
                ];
            });
        $teamCounts = [
            'all' => $members->count(),
            'owner' => $members->where('role', 'Owner')->count(),
            'member' => $members->where('role', 'Member')->count(),
        ];
        $projectCount = Project::query()->visibleTo($user)->count();
        $assignedTasksCount = $tasks->filter(fn (Task $task): bool => $task->assignees->isNotEmpty())->count();
        $teamStats = [
            ['label' => 'Team members', 'value' => $teamCounts['all'], 'note' => 'In your workspace', 'icon' => 'users', 'bg' => 'bg-indigo-50', 'fg' => 'text-indigo-600'],
            ['label' => 'Projects', 'value' => $projectCount, 'note' => 'Available to you', 'icon' => 'folder', 'bg' => 'bg-violet-50', 'fg' => 'text-violet-600'],
            ['label' => 'Tasks assigned', 'value' => $assignedTasksCount, 'note' => 'With at least one teammate', 'icon' => 'list', 'bg' => 'bg-amber-50', 'fg' => 'text-amber-600'],
            ['label' => 'Tasks completed', 'value' => $tasks->filter(fn (Task $task): bool => $task->status === TaskStatus::Completed)->count(), 'note' => 'Across visible projects', 'icon' => 'check', 'bg' => 'bg-emerald-50', 'fg' => 'text-emerald-600'],
        ];

        return view('team', [
            'teamMembers' => $members,
            'teamCounts' => $teamCounts,
            'teamStats' => $teamStats,
        ]);
    }
}
