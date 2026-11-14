<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        User::query()->lazyById()->each(function (User $user): void {
            $user->ensureDefaultProjects();
        });
    }

    public function down(): void
    {
        // no-op: default project entries are intentionally preserved for existing users
    }
};
