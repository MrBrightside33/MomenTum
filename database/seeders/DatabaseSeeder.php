<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $demoUsers = [
            [
                'name' => 'Demo Administrator',
                'email' => 'admin@momentum.test',
                'role' => UserRole::Admin,
                'is_active' => true,
                'last_login_at' => now()->subHours(2),
                'last_seen_at' => now()->subMinutes(20),
            ],
        ];

        foreach (range(1, 3) as $index) {
            $demoUsers[] = [
                'name' => "Demo Manager {$index}",
                'email' => sprintf('manager%02d@momentum.test', $index),
                'role' => UserRole::Manager,
                'is_active' => true,
                'last_login_at' => now()->subDays($index),
                'last_seen_at' => now()->subDays($index)->addMinutes(10),
            ];
        }

        foreach (range(1, 23) as $index) {
            $isActive = $index % 8 !== 0;
            $hasSignedIn = $isActive && $index % 6 !== 0;
            $lastLoginAt = $hasSignedIn ? now()->subDays($index % 13) : null;

            $demoUsers[] = [
                'name' => sprintf('Demo Member %02d', $index),
                'email' => sprintf('member%02d@momentum.test', $index),
                'role' => UserRole::Member,
                'is_active' => $isActive,
                'last_login_at' => $lastLoginAt,
                'last_seen_at' => $lastLoginAt?->copy()->addMinutes(15),
            ];
        }

        $demoUsers[] = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::Member,
            'is_active' => true,
        ];

        $actions = [
            'auth.login',
            'auth.logout',
            'auth.registered',
            'task.created',
            'task.updated',
            'project.created',
        ];

        foreach ($demoUsers as $index => $attributes) {
            $user = User::query()->where('email', $attributes['email'])->first();

            if ($user === null) {
                $factory = match ($attributes['role']) {
                    UserRole::Admin => User::factory()->admin(),
                    UserRole::Manager => User::factory()->manager(),
                    UserRole::Member => User::factory(),
                };
                $user = $factory->create($attributes);
            }

            $action = $actions[$index % count($actions)];
            $description = "Demo event: {$action} for {$user->name}.";

            if (! AuditLog::query()->where('user_id', $user->getKey())->where('description', $description)->exists()) {
                AuditLog::factory()->create([
                    'user_id' => $user->getKey(),
                    'action' => $action,
                    'description' => $description,
                    'created_at' => now()->subDays($index % 30),
                ]);
            }
        }
    }
}
