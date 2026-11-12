<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->is_active
            && Task::query()->visibleTo($user)->whereKey($task->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $user->ownedProjects()->exists());
    }

    public function update(User $user, Task $task): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $task->created_by_id === $user->getKey());
    }

    public function changeStatus(User $user, Task $task): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $this->view($user, $task));
    }

    public function addComment(User $user, Task $task): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $this->view($user, $task));
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->ownsOrCreated($user, $task);
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return $this->delete($user, $task);
    }

    private function ownsOrCreated(User $user, Task $task): bool
    {
        return $user->is_active
            && ! $user->isAdmin()
            && ($user->isManager() || $task->created_by_id === $user->getKey());
    }
}
