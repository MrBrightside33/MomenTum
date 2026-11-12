<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskCommentRequest;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\TaskActivityNotification;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskCommentController extends Controller
{
    public function store(StoreTaskCommentRequest $request, Task $task, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $task->load(['project', 'assignees']);
        $comment = DB::transaction(function () use ($auditLogger, $request, $task): TaskComment {
            $comment = $task->comments()->create([
                'user_id' => $request->user()->getKey(),
                'body' => $request->validated('body'),
            ]);
            $task->activities()->create([
                'user_id' => $request->user()->getKey(),
                'event_type' => 'commented',
                'metadata' => ['comment_id' => $comment->getKey()],
            ]);
            $auditLogger->record('task.commented', $task, 'A comment was added to task: '.$task->title, [
                'comment_id' => $comment->getKey(),
            ], $request->user());

            return $comment->load('user:id,name');
        });
        $recipientIds = array_unique([
            $task->created_by_id,
            $task->project->owner_id,
            ...$task->assignees->pluck('id')->all(),
        ]);
        User::query()
            ->active()
            ->whereIn('id', array_diff($recipientIds, [$request->user()->getKey()]))
            ->get()
            ->each(function (User $recipient) use ($task, $request): void {
                if (($recipient->settings['notifications']['team_activity'] ?? true) === true) {
                    $recipient->notify(new TaskActivityNotification('task_comment', $task, $request->user()));
                }
            });

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
