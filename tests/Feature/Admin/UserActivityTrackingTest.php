<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserActivityTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_last_seen_is_written_at_most_once_per_minute(): void
    {
        $user = User::factory()->create();
        Cache::forget('seen:'.$user->getKey());
        $this->travelTo('2026-11-12 08:00:00');

        $this->actingAs($user)
            ->get(route('settings'))
            ->assertOk();

        $firstSeen = $user->fresh()->last_seen_at->toDateTimeString();
        $this->travelTo('2026-11-12 08:00:30');

        $this->get(route('settings'))->assertOk();
        $this->assertSame($firstSeen, $user->fresh()->last_seen_at->toDateTimeString());

        $this->travelTo('2026-11-12 08:01:01');
        $this->get(route('settings'))->assertOk();
        $this->assertSame('2026-11-12 08:01:01', $user->fresh()->last_seen_at->toDateTimeString());
    }

    public function test_successful_login_records_login_and_seen_timestamps(): void
    {
        $user = User::factory()->create();
        $this->travelTo('2026-11-12 09:00:00');

        $this->post(route('login.submit'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        $this->assertSame('2026-11-12 09:00:00', $user->fresh()->last_login_at->toDateTimeString());
        $this->assertSame('2026-11-12 09:00:00', $user->fresh()->last_seen_at->toDateTimeString());
    }
}
