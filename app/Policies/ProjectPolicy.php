<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->is_active
            && Project::query()->visibleTo($user)->whereKey($project->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active && ! $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && $project->owner_id === $user->getKey();
    }

    public function archive(User $user, Project $project): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $project->owner_id === $user->getKey());
    }

    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
