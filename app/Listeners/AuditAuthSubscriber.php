<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;

class AuditAuthSubscriber
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Login::class, [self::class, 'handleLogin']);
        $events->listen(Failed::class, [self::class, 'handleFailed']);
        $events->listen(Logout::class, [self::class, 'handleLogout']);
        $events->listen(Registered::class, [self::class, 'handleRegistered']);
        $events->listen(PasswordReset::class, [self::class, 'handlePasswordReset']);
    }

    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->auditLogger->record('auth.login', $event->user, 'User signed in.', [], $event->user);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = Str::limit((string) ($event->credentials['email'] ?? ''), 120, '');

        $this->auditLogger->record('auth.login_failed', null, 'A sign-in attempt failed.', [
            'email' => $email,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->auditLogger->record('auth.logout', $event->user, 'User signed out.', [], $event->user);
        }
    }

    public function handleRegistered(Registered $event): void
    {
        if ($event->user instanceof User) {
            $this->auditLogger->record('auth.registered', $event->user, 'User registered.', [], $event->user);
        }
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->auditLogger->record('auth.password_reset', $event->user, 'User reset their password.', [], $event->user);
        }
    }
}
