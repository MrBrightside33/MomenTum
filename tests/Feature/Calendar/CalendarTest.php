<?php

namespace Tests\Feature\Calendar;

use Tests\TestCase;

class CalendarTest extends TestCase
{
    public function test_calendar_shows_deadlines_and_schedule_controls(): void
    {
        $response = $this->get(route('calendar'));

        $response
            ->assertOk()
            ->assertSee('Calendar')
            ->assertSee('2 deadlines due today')
            ->assertSee('1 more due tomorrow')
            ->assertSee('Finalize mobile app wireframes')
            ->assertSee('data-calendar-filter="deadline"', false)
            ->assertSee('data-calendar-schedule-form', false)
            ->assertSee('data-theme-authenticated="false"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('dashboard').'#task-row-sample-task-1', false);
    }
}
