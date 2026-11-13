<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Observers\TaskObserver;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([TaskObserver::class])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    use Prunable;
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'created_by_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'completed_by_id',
        'progress',
        'progress_before_complete',
        'progress_updated_at',
        'progress_updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'completed_by_id' => 'integer',
            'progress' => 'integer',
            'progress_before_complete' => 'integer',
            'progress_updated_at' => 'datetime',
            'progress_updated_by_id' => 'integer',
        ];
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()
            ->where('deleted_at', '<=', now()->subDays(config('app.task_retention_days', 30)));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id');
    }

    public function progressUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'progress_updated_by_id');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isManager()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereHas('project', fn (Builder $query): Builder => $query->whereBelongsTo($user, 'owner'))
                ->orWhereBelongsTo($user, 'creator')
                ->orWhereHas('assignees', fn (Builder $query): Builder => $query->whereKey($user->getKey()));
        });
    }

    public function scopeAtProgress(Builder $query, int $progress): Builder
    {
        return $query->where('progress', $progress);
    }
}
