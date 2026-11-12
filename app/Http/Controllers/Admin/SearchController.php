<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['q'] ?? '');

        $users = $search === '' ? collect() : User::query()
            ->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'role', 'avatar_path']);
        $tasks = $search === '' ? collect() : Task::query()
            ->with(['project:id,name', 'creator:id,name'])
            ->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhereHas('project', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'project_id', 'created_by_id', 'title', 'status', 'created_at']);
        $projects = $search === '' ? collect() : Project::query()
            ->with('owner:id,name')
            ->withCount([
                'tasks',
                'tasks as incomplete_tasks_count' => fn (Builder $query): Builder => $query->where(
                    'status',
                    '!=',
                    TaskStatus::Completed->value,
                ),
            ])
            ->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'owner_id', 'name', 'status']);
        $projects->each(function (Project $project): void {
            $project->setAttribute(
                'effective_status',
                $project->effectiveStatusFromCounts($project->tasks_count, $project->incomplete_tasks_count)->value,
            );
        });

        return view('admin.search', [
            'query' => $search,
            'users' => $users,
            'tasks' => $tasks,
            'projects' => $projects,
        ]);
    }
}
