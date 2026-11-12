<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function download(string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, ['users', 'tasks', 'projects', 'audit'], true), 404);

        return response()->streamDownload(function () use ($dataset): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new RuntimeException('Unable to open the CSV output stream.');
            }

            try {
                if (fwrite($output, "\xEF\xBB\xBF") === false) {
                    throw new RuntimeException('Unable to write the CSV header.');
                }

                $this->writeDataset($output, $dataset);
            } finally {
                fclose($output);
            }
        }, $dataset.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  resource  $output
     */
    private function writeDataset(mixed $output, string $dataset): void
    {
        switch ($dataset) {
            case 'users':
                $this->writeUsers($output);
                break;
            case 'tasks':
                $this->writeTasks($output);
                break;
            case 'projects':
                $this->writeProjects($output);
                break;
            case 'audit':
                $this->writeAuditLogs($output);
                break;
        }
    }

    /**
     * @param  resource  $output
     */
    private function writeUsers(mixed $output): void
    {
        $this->writeRow($output, ['ID', 'Name', 'Email', 'Role', 'Status', 'Job title', 'Created at', 'Last active']);

        foreach (User::query()
            ->select(['id', 'name', 'email', 'role', 'is_active', 'job_title', 'created_at', 'last_login_at', 'last_seen_at'])
            ->lazyById(500) as $user) {
            $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
            $this->writeRow($output, [
                $user->getKey(),
                $user->name,
                $user->email,
                $role,
                $user->accountStatus(),
                $user->job_title,
                $user->created_at?->toIso8601String(),
                ($user->last_seen_at ?? $user->last_login_at)?->toIso8601String(),
            ]);
        }
    }

    /**
     * @param  resource  $output
     */
    private function writeTasks(mixed $output): void
    {
        $this->writeRow($output, ['ID', 'Title', 'Project', 'Creator', 'Status', 'Priority', 'Due date', 'Created at']);

        foreach (Task::query()
            ->select(['id', 'project_id', 'created_by_id', 'title', 'status', 'priority', 'due_date', 'created_at'])
            ->with(['project:id,name', 'creator:id,name'])
            ->lazyById(500) as $task) {
            $status = $task->status instanceof TaskStatus ? $task->status->value : (string) $task->status;
            $priority = $task->priority instanceof TaskPriority ? $task->priority->value : (string) $task->priority;
            $this->writeRow($output, [
                $task->getKey(),
                $task->title,
                $task->project?->name,
                $task->creator?->name,
                $status,
                $priority,
                $task->due_date?->toDateString(),
                $task->created_at?->toIso8601String(),
            ]);
        }
    }

    /**
     * @param  resource  $output
     */
    private function writeProjects(mixed $output): void
    {
        $this->writeRow($output, ['ID', 'Name', 'Owner', 'Status', 'Task count', 'Created at']);

        foreach (Project::query()
            ->select(['id', 'owner_id', 'name', 'status', 'created_at'])
            ->with('owner:id,name')
            ->withCount([
                'tasks',
                'tasks as incomplete_tasks_count' => static fn (Builder $query): Builder => $query->where(
                    'status',
                    '!=',
                    TaskStatus::Completed->value,
                ),
            ])
            ->lazyById(500) as $project) {
            $status = $project->effectiveStatusFromCounts(
                $project->tasks_count,
                $project->incomplete_tasks_count,
            )->value;
            $this->writeRow($output, [
                $project->getKey(),
                $project->name,
                $project->owner?->name,
                $status,
                $project->tasks_count,
                $project->created_at?->toIso8601String(),
            ]);
        }
    }

    /**
     * @param  resource  $output
     */
    private function writeAuditLogs(mixed $output): void
    {
        $this->writeRow($output, ['ID', 'Timestamp', 'Actor', 'Action', 'Description', 'Subject', 'Subject ID']);

        foreach (AuditLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'subject_type', 'subject_id', 'created_at'])
            ->with('actor:id,name,email')
            ->lazyById(500) as $auditLog) {
            $this->writeRow($output, [
                $auditLog->getKey(),
                $auditLog->created_at?->toIso8601String(),
                $auditLog->actor?->email,
                $auditLog->action,
                $auditLog->description,
                $auditLog->subject_type === null ? null : class_basename($auditLog->subject_type),
                $auditLog->subject_id,
            ]);
        }
    }

    /**
     * @param  resource  $output
     * @param  array<int, mixed>  $row
     */
    private function writeRow(mixed $output, array $row): void
    {
        if (! is_resource($output)) {
            throw new RuntimeException('The CSV output stream is not available.');
        }

        $values = array_map(static function (mixed $value): mixed {
            if (! is_string($value)) {
                return $value;
            }

            return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1 ? "'".$value : $value;
        }, $row);

        if (fputcsv($output, $values, ',', '"', '') === false) {
            throw new RuntimeException('Unable to write a CSV row.');
        }
    }
}
