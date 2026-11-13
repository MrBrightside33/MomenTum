<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Metrics\ProjectMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request, ProjectMetrics $projectMetrics): View
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
            ->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'owner_id', 'name', 'status']);
        $summaries = $projectMetrics->summaries(['project_ids' => $projects->modelKeys()]);
        $projects->each(function (Project $project) use ($summaries): void {
            $summary = $summaries[$project->getKey()] ?? [
                'tasks' => 0,
                'incomplete_tasks' => 0,
                'completed_tasks' => 0,
                'progress' => 0,
            ];
            $project->setAttribute(
                'effective_status',
                $project->effectiveStatusFromCounts($summary['tasks'], $summary['incomplete_tasks'])->value,
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
