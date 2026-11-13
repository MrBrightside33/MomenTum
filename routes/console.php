<?php

use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tasks:send-due-reminders')->hourly()->withoutOverlapping();
Schedule::command('audit:prune --days=180')->weekly()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [Task::class]])->daily()->withoutOverlapping();
