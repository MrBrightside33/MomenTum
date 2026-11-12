<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_map(
                static fn (TaskStatus $status): string => $status->value,
                TaskStatus::cases(),
            ))],
            'priority' => ['nullable', Rule::in(array_map(
                static fn (TaskPriority $priority): string => $priority->value,
                TaskPriority::cases(),
            ))],
        ]);
        $search = trim($validated['q'] ?? '');

        $tasks = Task::query()
            ->select(['id', 'project_id', 'created_by_id', 'title', 'status', 'priority', 'due_date', 'created_at'])
            ->with(['project:id,name', 'creator:id,name', 'assignees:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('project', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('creator', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['priority']), fn (Builder $query): Builder => $query->where('priority', $validated['priority']))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.tasks.index', [
            'tasks' => $tasks,
            'filters' => $validated,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
        ]);
    }
}
