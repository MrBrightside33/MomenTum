<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Observers\UserObserver;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'job_title', 'bio', 'avatar_path', 'settings'])]
#[Hidden(['password', 'remember_token'])]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by_id');
    }

    public function assignedTasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignees');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function taskActivities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    /** Active accounts have signed in; an active account with no login is still pending. */
    public function accountStatus(): string
    {
        foreach (config('metrics.users.statuses', []) as $status => $definition) {
            if ($this->is_active !== $definition['is_active']) {
                continue;
            }

            $hasSignedIn = $this->last_login_at !== null;

            if (
                $definition['last_login'] === 'any'
                || ($definition['last_login'] === 'required' && $hasSignedIn)
                || ($definition['last_login'] === 'missing' && ! $hasSignedIn)
            ) {
                return $status;
            }
        }

        return 'inactive';
    }

    public function scopeAccountStatus(Builder $query, string $status): Builder
    {
        $definition = config("metrics.users.statuses.{$status}");

        if (! is_array($definition)) {
            return $query;
        }

        $query->where('is_active', $definition['is_active']);

        return match ($definition['last_login']) {
            'required' => $query->whereNotNull('last_login_at'),
            'missing' => $query->whereNull('last_login_at'),
            default => $query,
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function ensureDefaultProjects(): void
    {
        $projectDescriptions = [
            'Personal' => 'Keep individual goals and focused work in one place.',
            'Team' => 'Keep shared work, handoffs, and collaboration in one place.',
        ];

        foreach ($projectDescriptions as $name => $description) {
            $this->ownedProjects()->firstOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }
    }

    public function defaultPageRoute(): string
    {
        $settings = $this->settings ?? [];

        if (($settings['default_page'] ?? 'dashboard') === 'landing') {
            return 'home';
        }

        return ($settings['default_task_view'] ?? 'list') === 'calendar'
            ? 'calendar'
            : 'dashboard';
    }
}
