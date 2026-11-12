<?php

namespace App\Http\Controllers;

use App\Actions\TaskWorkflow;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Services\TaskSearchService;
use App\Support\TaskPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function searchSuggestions(Request $request, TaskSearchService $searchService): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return response()->json([
            'data' => $searchService->suggestions($request->user(), $validated['q']),
        ]);
    }

    public function show(Task $task): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $task);

        return response()->json([
            'task' => (new TaskPresenter)->present($task),
        ]);
    }

    public function store(StoreTaskRequest $request, TaskWorkflow $workflow, TaskPresenter $presenter): JsonResponse|RedirectResponse
    {
        $task = $workflow->create($request->user(), $request->validated());
        $message = 'Task created';

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', $message);
        }

        return response()->json([
            'message' => $message,
            'task_id' => $task->getKey(),
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ], 201);
    }

    public function update(
        UpdateTaskRequest $request,
        Task $task,
        TaskWorkflow $workflow,
        TaskPresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $task = $workflow->update($task, $request->user(), $request->validated());
        $message = 'Task updated';

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', $message);
        }

        return response()->json([
            'message' => $message,
            'task_id' => $task->getKey(),
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ]);
    }

    public function updateStatus(
        UpdateTaskStatusRequest $request,
        Task $task,
        TaskWorkflow $workflow,
        TaskPresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $task = $workflow->changeStatus(
            $task,
            $request->user(),
            TaskStatus::from($request->validated('status')),
        );

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', 'Task status updated');
        }

        return response()->json([
            'message' => 'Task status updated',
            'task_id' => $task->getKey(),
            'status' => $task->status->value,
            'done' => $task->status === TaskStatus::Completed,
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ]);
    }

    public function destroy(Request $request, Task $task, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $task);
        $taskId = $task->getKey();
        DB::transaction(function () use ($auditLogger, $request, $task): void {
            $auditLogger->record('task.deleted', $task, 'Task deleted: '.$task->title, [], $request->user());
            $task->delete();
        });

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', 'Task deleted');
        }

        return response()->json([
            'message' => 'Task deleted',
            'task_id' => $taskId,
        ]);
    }
}
