<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly Task $task,
        private readonly ?User $actor = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->task->loadMissing('project');

        return [
            'event' => $this->event,
            'category' => match ($this->event) {
                'task_assigned' => 'task_assignments',
                'task_status_changed' => 'task_status_updates',
                'task_comment' => 'team_activity',
                default => 'due_date_reminders',
            },
            'actor_id' => $this->actor?->getKey(),
            'actor_name' => $this->actor?->name ?? 'MomenTum',
            'task_id' => $this->task->getKey(),
            'task_title' => $this->task->title,
            'project_name' => $this->task->project?->name,
            'url' => route('dashboard').'#task-row-'.$this->task->getKey(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
