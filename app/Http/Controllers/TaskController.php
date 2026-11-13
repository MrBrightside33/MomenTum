<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskProgressRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use App\Services\TaskSearchService;
use App\Services\TaskService;
use App\Support\TaskPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Request $request, Task $task, TaskPresenter $presenter): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $task);

        return response()->json([
            'task' => $presenter->present($task, $request->user()),
        ]);
    }

    public function store(StoreTaskRequest $request, TaskService $service, TaskPresenter $presenter): JsonResponse|RedirectResponse
    {
        $task = $service->create($request->user(), $request->validated());
        $message = 'Task created';

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', $message);
        }

        return response()->json([
            'message' => $message,
            'task_id' => $task->getKey(),
            'task' => $presenter->present($task, $request->user()),
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ], 201);
    }

    public function update(
        UpdateTaskRequest $request,
        Task $task,
        TaskService $service,
        TaskPresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $task = $service->update($task, $request->user(), $request->validated());
        $message = 'Task updated';

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', $message);
        }

        return response()->json([
            'message' => $message,
            'task_id' => $task->getKey(),
            'task' => $presenter->present($task, $request->user()),
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ]);
    }

    public function updateStatus(
        UpdateTaskStatusRequest $request,
        Task $task,
        TaskService $service,
        TaskPresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $task = $service->changeStatus(
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
            'task' => $presenter->present($task, $request->user()),
            'html' => view('tasks._row', ['task' => $presenter->present($task, $request->user())])->render(),
        ]);
    }

    public function progress(
        UpdateTaskProgressRequest $request,
        Task $task,
        TaskService $service,
        TaskPresenter $presenter,
    ): JsonResponse {
        $wasCompleted = $task->status === TaskStatus::Completed;
        $updatedTask = $service->setProgress(
            $task,
            $request->integer('progress'),
            $request->integer('expected_progress'),
            $request->user(),
        );
        $message = match (true) {
            ! $wasCompleted && $updatedTask->status === TaskStatus::Completed => 'Task completed. Progress set to 100%.',
            $wasCompleted && $updatedTask->status !== TaskStatus::Completed => 'Task reopened',
            default => 'Progress updated',
        };

        return response()->json([
            'message' => $message,
            'task' => $presenter->present($updatedTask, $request->user()),
        ]);
    }

    public function destroy(Request $request, Task $task, TaskService $service): JsonResponse|RedirectResponse
    {
        Gate::authorize('delete', $task);
        $taskId = $task->getKey();
        $service->delete($task, $request->user());

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', 'Task moved to trash');
        }

        return response()->json([
            'message' => 'Task moved to trash',
            'task_id' => $taskId,
            'restore_url' => route('tasks.restore', ['task' => $taskId]),
        ]);
    }

    public function restore(
        Request $request,
        int $task,
        TaskService $service,
        TaskPresenter $presenter,
    ): JsonResponse|RedirectResponse {
        $trashedTask = Task::onlyTrashed()->findOrFail($task);
        Gate::authorize('restore', $trashedTask);

        $trashedTask = $service->restore($trashedTask, $request->user());

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', 'Task restored');
        }

        $taskData = $presenter->present($trashedTask, $request->user());

        return response()->json([
            'message' => 'Task restored',
            'task_id' => $trashedTask->getKey(),
            'task' => $taskData,
            'html' => view('tasks._row', ['task' => $taskData])->render(),
        ]);
    }
}
