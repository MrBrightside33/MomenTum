<?php

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function created(Project $project): void
    {
        $this->auditLogger->record(
            'project.created',
            $project,
            'Project created: '.$project->name,
            [],
            $this->actor($project),
        );
    }

    public function updated(Project $project): void
    {
        $changedFields = array_values(array_diff(array_keys($project->getChanges()), ['updated_at']));

        if ($changedFields === []) {
            return;
        }

        $previous = $project->getPrevious();
        $previousStatus = $previous['status'] ?? null;
        $wasArchived = $previousStatus === ProjectStatus::Archived
            || $previousStatus === ProjectStatus::Archived->value;
        $isArchived = $project->status === ProjectStatus::Archived;

        [$action, $description] = match (true) {
            $isArchived && ! $wasArchived => ['project.archived', 'Project archived: '.$project->name],
            $wasArchived && ! $isArchived => ['project.restored', 'Project restored: '.$project->name],
            default => ['project.updated', 'Project updated: '.$project->name],
        };

        $this->auditLogger->record($action, $project, $description, [
            'changed_fields' => $changedFields,
        ]);
    }

    public function deleted(Project $project): void
    {
        $this->auditLogger->record('project.deleted', $project, 'Project deleted: '.$project->name);
    }

    public function restored(Project $project): void
    {
        $this->auditLogger->record('project.restored', $project, 'Project restored: '.$project->name);
    }

    private function actor(Project $project): ?User
    {
        $actor = Auth::user();

        if ($actor instanceof User) {
            return $actor;
        }

        $owner = $project->owner;

        return $owner instanceof User ? $owner : null;
    }
}
