<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DueTaskReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tasks:send-due-reminders')]
#[Description('Send timezone-aware reminders for tasks due today or tomorrow')]
class SendDueTaskReminders extends Command
{
    public function handle(DueTaskReminderService $reminderService): int
    {
        $sentCount = 0;

        User::query()->active()->get()->each(function (User $user) use ($reminderService, &$sentCount): void {
            $localNow = $reminderService->localNow($user);

            if ($localNow->hour !== 8) {
                return;
            }

            $sentCount += $reminderService->sendDueReminders($user, $localNow, includeTomorrow: true);
        });

        $this->info("Sent {$sentCount} due-task reminder(s).");

        return self::SUCCESS;
    }
}
