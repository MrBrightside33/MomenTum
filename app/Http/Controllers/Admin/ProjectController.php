<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Metrics\ProjectMetrics;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectMetrics $projectMetrics): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_map(
                static fn (ProjectStatus $status): string => $status->value,
                ProjectStatus::cases(),
            ))],
        ]);
        $search = trim($validated['q'] ?? '');

        $projects = Project::query()
            ->select(['id', 'owner_id', 'name', 'description', 'status', 'created_at'])
            ->with('owner:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($validated['status']),
                fn (Builder $query): Builder => $query->effectiveStatus(ProjectStatus::from($validated['status'])),
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $summaries = $projectMetrics->summaries([
            'project_ids' => $projects->getCollection()->modelKeys(),
        ]);
        $projects->getCollection()->each(function (Project $project) use ($summaries): void {
            $summary = $summaries[$project->getKey()] ?? [
                'tasks' => 0,
                'incomplete_tasks' => 0,
                'completed_tasks' => 0,
                'progress' => 0,
            ];
            $project->setAttribute('tasks_count', $summary['tasks']);
            $project->setAttribute('incomplete_tasks_count', $summary['incomplete_tasks']);
            $project->setAttribute('progress', $summary['progress']);
            $project->setAttribute(
                'effective_status',
                $project->effectiveStatusFromCounts($project->tasks_count, $project->incomplete_tasks_count)->value,
            );
        });

        return view('admin.projects.index', [
            'projects' => $projects,
            'filters' => $validated,
            'statuses' => ProjectStatus::cases(),
        ]);
    }
}
