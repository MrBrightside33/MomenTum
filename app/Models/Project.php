<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereBelongsTo($user, 'owner')
                ->orWhereHas('tasks', fn (Builder $query): Builder => $query->whereBelongsTo($user, 'creator'))
                ->orWhereHas('tasks.assignees', fn (Builder $query): Builder => $query->whereKey($user->getKey()));
        });
    }

    public function scopeEffectiveStatus(Builder $query, ProjectStatus $status): Builder
    {
        $hasIncompleteTasks = fn (Builder $tasks): Builder => $tasks->where('status', '!=', TaskStatus::Completed->value);

        return match ($status) {
            ProjectStatus::Archived => $query->where('status', ProjectStatus::Archived->value),
            ProjectStatus::Planning => $query
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->whereDoesntHave('tasks'),
            ProjectStatus::InProgress => $query
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->whereHas('tasks', $hasIncompleteTasks),
            ProjectStatus::Completed => $query
                ->where('status', '!=', ProjectStatus::Archived->value)
                ->whereHas('tasks')
                ->whereDoesntHave('tasks', $hasIncompleteTasks),
        };
    }

    public function effectiveStatusFromCounts(int $taskCount, int $incompleteTaskCount): ProjectStatus
    {
        if ($this->status === ProjectStatus::Archived) {
            return ProjectStatus::Archived;
        }

        return match (true) {
            $taskCount === 0 => ProjectStatus::Planning,
            $incompleteTaskCount === 0 => ProjectStatus::Completed,
            default => ProjectStatus::InProgress,
        };
    }
}
