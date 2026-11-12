<?php

namespace Tests\Unit;

use App\Support\LastActive;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class LastActiveTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_formats_recent_activity_and_falls_back_to_last_login(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-10 12:00:00', 'UTC'));

        $this->assertSame(
            'Now',
            LastActive::label(Carbon::now('UTC')->subSeconds(119), null, 'UTC'),
        );
        $this->assertSame(
            '2 min ago',
            LastActive::label(Carbon::now('UTC')->subSeconds(120), null, 'UTC'),
        );
        $this->assertSame(
            '1 hour ago',
            LastActive::label(null, Carbon::now('UTC')->subHour(), 'UTC'),
        );
        $this->assertSame('Invitation sent', LastActive::label(null, null, 'UTC'));
    }

    public function test_it_formats_calendar_yesterday_and_older_activity(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-10 23:30:00', 'UTC'));

        $this->assertSame(
            'Yesterday',
            LastActive::label(Carbon::parse('2026-03-09 23:00:00', 'UTC'), null, 'UTC'),
        );
        $this->assertSame(
            'Mar 3, 2026',
            LastActive::label(Carbon::parse('2026-03-03 22:00:00', 'UTC'), null, 'UTC'),
        );

        Carbon::setTestNow(Carbon::parse('2026-03-10 01:00:00', 'UTC'));

        $this->assertSame(
            'Yesterday',
            LastActive::label(Carbon::parse('2026-03-08 22:00:00', 'UTC'), null, 'Pacific/Honolulu'),
        );
    }
}
