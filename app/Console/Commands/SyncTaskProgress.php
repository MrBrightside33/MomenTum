<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:sync-progress')]
#[Description('Restore the task progress and completion invariant')]
class SyncTaskProgress extends Command
{
    public function handle(): int
    {
        $fixedCount = 0;

        Task::query()->chunkById(100, function ($tasks) use (&$fixedCount): void {
            foreach ($tasks as $task) {
                if ($task->status === TaskStatus::Completed) {
                    $task->progress = 100;
                } elseif ($task->progress === 100) {
                    $task->status = TaskStatus::Completed;
                }

                if ($task->isDirty()) {
                    $task->save();
                    $fixedCount++;
                }
            }
        });

        $this->info("Fixed {$fixedCount} task records.");

        return self::SUCCESS;
    }
}
