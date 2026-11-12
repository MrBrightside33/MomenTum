<?php

namespace App\View\Composers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\View\View;

class AdminNavigationComposer
{
    public function compose(View $view): void
    {
        $view->with([
            'adminMemberCount' => User::query()->count(),
            'failedSignInCount' => AuditLog::query()
                ->where('action', 'auth.login_failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
        ]);
    }
}
