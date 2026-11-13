<?php

namespace Tests\Feature\Support\Metrics;

use App\Models\User;
use App\Support\Metrics\UserMetrics;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserMetricsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_account_status_counts_and_active_admin_count_match_the_defined_account_states(): void
    {
        $activeAdmin = User::factory()->admin()->create(['last_login_at' => now()]);
        $activeMember = User::factory()->create(['last_login_at' => now()]);
        $pendingMember = User::factory()->create(['last_login_at' => null]);
        $inactiveAdmin = User::factory()->admin()->inactive()->create(['last_login_at' => now()]);

        $counts = app(UserMetrics::class)->counts();

        $this->assertSame(4, $counts['total']);
        $this->assertSame(2, $counts['members']);
        $this->assertSame(2, $counts['active']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(1, $counts['inactive']);
        $this->assertSame(1, $counts['active_admins']);
        $this->assertSame('active', $activeAdmin->accountStatus());
        $this->assertSame('active', $activeMember->accountStatus());
        $this->assertSame('pending', $pendingMember->accountStatus());
        $this->assertSame('inactive', $inactiveAdmin->accountStatus());
    }
}
