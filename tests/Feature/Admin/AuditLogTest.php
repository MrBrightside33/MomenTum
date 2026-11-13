<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_failed_and_logout_events_are_audited_without_credentials(): void
    {
        $user = User::factory()->create(['email' => 'audit@example.com']);

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'user_id' => $user->getKey(),
        ]);

        $this->post(route('logout'))->assertRedirectToRoute('home');

        $this->from(route('login'))
            ->post(route('login.submit'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirectToRoute('login');

        $failedLogin = AuditLog::query()->where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame(['email' => $user->email], $failedLogin->metadata);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->getKey(),
        ]);
    }

    public function test_audit_logs_cannot_be_updated_or_deleted_through_the_model(): void
    {
        $auditLog = AuditLog::factory()->create(['description' => 'Original entry']);
        $auditLog->description = 'Changed entry';

        $this->assertFalse($auditLog->save());
        $this->assertFalse($auditLog->fresh()->delete());

        $this->assertDatabaseHas('audit_logs', [
            'id' => $auditLog->getKey(),
            'description' => 'Original entry',
        ]);
    }

    public function test_prune_command_deletes_only_rows_older_than_the_requested_retention_period(): void
    {
        $oldEntry = AuditLog::factory()->create([
            'created_at' => now()->subDays(181),
            'user_id' => null,
        ]);
        $recentEntry = AuditLog::factory()->create([
            'created_at' => now()->subDays(30),
            'user_id' => null,
        ]);

        $this->artisan('audit:prune', ['--days' => 180])
            ->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['id' => $oldEntry->getKey()]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recentEntry->getKey()]);
        $this->assertSame(1, DB::table('audit_logs')->count());
    }
}
