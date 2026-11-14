<?php

namespace App\View\Composers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use App\Support\TaskPresenter;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskModalComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $projects = [];
        $team = [];

        if ($user instanceof User) {
            $projects = $user->isAdmin()
                ? Project::query()->orderBy('name')->get(['id', 'name'])
                : Project::visibleTo($user)->orderBy('name')->get(['id', 'name']);
            $projects = $projects
                ->map(fn (Project $project): array => ['id' => $project->getKey(), 'name' => $project->name])
                ->all();
            $team = User::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $member): array => [
                    'id' => (string) $member->getKey(),
                    'name' => $member->name,
                    'initials' => TaskPresenter::initials($member->name),
                ])
                ->all();
        }

        $view->with([
            'projects' => $projects,
            'team' => $team,
            'priorities' => array_map(fn (TaskPriority $priority): string => $priority->value, TaskPriority::cases()),
            'statuses' => array_map(fn (TaskStatus $status): string => $status->value, TaskStatus::cases()),
            'selectedAssignees' => array_values(array_filter(
                $team,
                fn (array $member): bool => $user instanceof User && (string) $user->getKey() === $member['id'],
            )),
        ]);
    }
}
