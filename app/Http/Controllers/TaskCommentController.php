<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCommentRequest;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TaskCommentController extends Controller
{
    public function store(StoreTaskCommentRequest $request, Task $task, TaskService $service): JsonResponse|RedirectResponse
    {
        $comment = $service->comment($task, $request->user(), $request->validated('body'));

        if (! $request->expectsJson()) {
            return redirect()->route('dashboard')->with('status', 'Comment added');
        }

        return response()->json([
            'message' => 'Comment added',
            'comment' => [
                'id' => $comment->getKey(),
                'body' => $comment->body,
                'author' => $comment->user->name,
                'created_at' => $comment->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
