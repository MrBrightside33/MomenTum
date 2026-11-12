<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->orderBy('id')->chunkById(100, function ($users): void {
            $timestamp = now();
            $projects = $users
                ->map(fn (object $user): array => [
                    'owner_id' => $user->id,
                    'name' => 'Personal',
                    'description' => 'Keep individual goals and focused work in one place.',
                    'status' => 'planning',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ])
                ->all();

            DB::table('projects')->insertOrIgnore($projects);
        });
    }

    public function down(): void {}
};
