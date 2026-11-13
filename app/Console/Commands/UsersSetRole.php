<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('users:set-role {email} {role}')]
#[Description('Change a user role while protecting the last active administrator')]
class UsersSetRole extends Command
{
    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));
        $role = UserRole::tryFrom(Str::lower((string) $this->argument('role')));

        if ($role === null) {
            $this->error('Role must be one of: '.implode(', ', array_column(UserRole::cases(), 'value')).'.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        $result = DB::transaction(function () use ($role, $user): array {
            // Lock active administrators before checking the last-admin safeguard.
            $activeAdmins = User::query()
                ->where('role', UserRole::Admin)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get(['id']);
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $previousRole = $lockedUser->role;

            if ($previousRole === $role) {
                return ['status' => 'unchanged', 'previous_role' => $previousRole];
            }

            if (
                $lockedUser->is_active
                && $previousRole === UserRole::Admin
                && $role !== UserRole::Admin
                && $activeAdmins->count() <= 1
            ) {
                return ['status' => 'last_admin', 'previous_role' => $previousRole];
            }

            $lockedUser->forceFill(['role' => $role])->save();

            return ['status' => 'changed', 'previous_role' => $previousRole];
        });

        if ($result['status'] === 'last_admin') {
            $this->error('The last active administrator cannot be demoted.');

            return self::FAILURE;
        }

        if ($result['status'] === 'unchanged') {
            $this->info("{$user->email} already has the {$role->value} role.");

            return self::SUCCESS;
        }

        $this->info("{$user->email} role changed from {$result['previous_role']->value} to {$role->value}.");

        return self::SUCCESS;
    }
}
