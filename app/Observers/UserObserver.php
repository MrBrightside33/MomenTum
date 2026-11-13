<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\AuditContext;

class UserObserver
{
    public function __construct(
        private AuditLogger $auditLogger,
        private AuditContext $auditContext,
    ) {}

    public function created(User $user): void
    {
        if ($this->auditContext->isServiceWrite(User::class)) {
            return;
        }

        $this->auditLogger->record('user.created', $user, 'User account created: '.$user->name);
    }

    public function updated(User $user): void
    {
        if ($this->auditContext->isServiceWrite(User::class)) {
            return;
        }

        $previous = $user->getPrevious();

        if ($user->wasChanged('role')) {
            $oldRole = $previous['role'] ?? null;
            $newRole = $user->role;
            $this->auditLogger->record('user.role_changed', $user, 'A user role was changed.', [
                'old_role' => $oldRole instanceof UserRole ? $oldRole->value : $oldRole,
                'new_role' => $newRole instanceof UserRole ? $newRole->value : (string) $newRole,
            ]);
        }

        if ($user->wasChanged('is_active')) {
            $action = $user->is_active ? 'user.reactivated' : 'user.deactivated';
            $description = $user->is_active ? 'User account reactivated.' : 'User account deactivated.';
            $this->auditLogger->record($action, $user, $description);
        }

        if ($user->wasChanged('password')) {
            $this->auditLogger->record('user.password_changed', $user, 'User password changed.', [
                'changed_fields' => ['password'],
            ]);
        }

        $changedFields = array_values(array_intersect(array_keys($user->getChanges()), [
            'name',
            'email',
            'job_title',
            'bio',
            'avatar_path',
        ]));

        if ($changedFields !== []) {
            $this->auditLogger->record('user.profile_updated', $user, 'User profile updated.', [
                'changed_fields' => $changedFields,
            ]);
        }

        if ($user->wasChanged('settings')) {
            $preferenceFields = $this->changedPreferenceFields(
                $this->settings($previous['settings'] ?? null),
                $user->settings ?? [],
            );

            if ($preferenceFields !== []) {
                $this->auditLogger->record('user.preferences_updated', $user, 'User preferences updated.', [
                    'changed_fields' => $preferenceFields,
                ]);
            }
        }
    }

    public function deleted(User $user): void
    {
        if ($this->auditContext->isServiceWrite(User::class)) {
            return;
        }

        $this->auditLogger->record('user.deleted', $user, 'User account deleted.');
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $current
     * @return array<int, string>
     */
    private function changedPreferenceFields(array $previous, array $current): array
    {
        $preferenceKeys = [
            'workspace_name',
            'default_page',
            'default_task_view',
            'timezone',
            'appearance',
        ];
        $changedFields = [];

        foreach ($preferenceKeys as $key) {
            if (($previous[$key] ?? null) !== ($current[$key] ?? null)) {
                $changedFields[] = $key === 'appearance' ? 'theme' : $key;
            }
        }

        $notificationKeys = array_unique([
            ...array_keys($previous['notifications'] ?? []),
            ...array_keys($current['notifications'] ?? []),
        ]);

        foreach ($notificationKeys as $key) {
            if (
                ($previous['notifications'][$key] ?? null)
                !== ($current['notifications'][$key] ?? null)
            ) {
                $changedFields[] = 'notifications.'.$key;
            }
        }

        return $changedFields;
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(mixed $settings): array
    {
        if (is_array($settings)) {
            return $settings;
        }

        if (is_string($settings)) {
            $decoded = json_decode($settings, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
