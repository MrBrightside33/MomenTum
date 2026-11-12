<?php

namespace Tests\Feature\Team;

use Tests\TestCase;

class TeamTest extends TestCase
{
    public function test_team_page_shows_members_workload_and_filters(): void
    {
        $response = $this->get(route('team'));

        $response
            ->assertOk()
            ->assertSee('Team')
            ->assertSee('Alex Morgan')
            ->assertSee('Lina Kim')
            ->assertSee('Assigned tasks')
            ->assertSee('data-team-filter="member"', false)
            ->assertSee('data-team-search', false)
            ->assertSee('aria-current="page"', false);
    }
}
