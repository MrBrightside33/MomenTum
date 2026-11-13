<?php

namespace App\Exceptions;

use App\Models\Task;
use App\Support\TaskPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProgressConflictException extends RuntimeException
{
    public function __construct(public readonly Task $task)
    {
        parent::__construct('This task was updated elsewhere.');
    }

    public function report(): bool
    {
        return true;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'task' => app(TaskPresenter::class)->present($this->task, $request->user()),
        ], 409);
    }
}
