<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserRoleCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_cannot_demote_the_last_active_administrator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->artisan('users:set-role', [
            'email' => $admin->email,
            'role' => 'manager',
        ])
            ->expectsOutput('The last active administrator cannot be demoted.')
            ->assertExitCode(1);

        $this->assertSame('admin', $admin->fresh()->role->value);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.role_changed']);
    }

    public function test_command_changes_a_role_and_records_the_old_and_new_values(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->artisan('users:set-role', [
            'email' => $user->email,
            'role' => 'manager',
        ])
            ->expectsOutput("{$user->email} role changed from member to manager.")
            ->assertSuccessful();

        $this->assertSame('manager', $user->fresh()->role->value);
        $auditLog = AuditLog::query()->where('action', 'user.role_changed')->firstOrFail();
        $this->assertNull($auditLog->user_id);
        $this->assertSame([
            'old_role' => 'member',
            'new_role' => 'manager',
        ], $auditLog->metadata);
    }
}
