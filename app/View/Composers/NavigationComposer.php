<?php

namespace App\View\Composers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with([
            'unreadInboxCount' => $user instanceof User
                ? $user->unreadNotifications()->whereNull('archived_at')->count()
                : 0,
            'canCreateTasks' => $user instanceof User && $user->can('create', Task::class),
        ]);
    }
}
