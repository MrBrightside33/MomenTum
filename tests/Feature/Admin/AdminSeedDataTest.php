<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminSeedDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_local_demo_seeding_creates_twenty_eight_accounts_and_audit_events_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(28, User::query()->count());
        $this->assertSame(28, AuditLog::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@momentum.test')->firstOrFail()->isAdmin());

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(28, User::query()->count());
        $this->assertSame(28, AuditLog::query()->count());
    }
}
