<?php

namespace Tests\Feature\Team;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_team_page_renders_members_and_workload_from_visible_tasks(): void
    {
        $owner = User::factory()->create(['name' => 'Alex Morgan']);
        $member = User::factory()->create(['name' => 'Lina Kim']);
        $otherOwner = User::factory()->create();
        $project = Project::factory()->for($owner, 'owner')->create(['name' => 'Product Design']);
        $privateProject = Project::factory()->for($otherOwner, 'owner')->create(['name' => 'Private project']);
        $assignedTask = Task::factory()->for($project)->create([
            'created_by_id' => $owner->getKey(),
            'title' => 'Finalize wireframes',
        ]);
        $assignedTask->assignees()->attach($member);
        Task::factory()->for($privateProject)->create([
            'created_by_id' => $otherOwner->getKey(),
            'title' => 'Private task',
        ]);

        $this->actingAs($owner)
            ->get(route('team'))
            ->assertOk()
            ->assertSee('Team')
            ->assertSee('Alex Morgan')
            ->assertSee('Lina Kim')
            ->assertDontSee('Private project')
            ->assertSee('Assigned tasks')
            ->assertSee('data-team-filter="member"', false)
            ->assertSee('data-team-search', false);
    }
}
