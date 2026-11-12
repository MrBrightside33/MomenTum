<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TaskSearchSuggestionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_search_field_connects_to_the_suggestions_list(): void
    {
        $user = User::factory()->create();
        Project::factory()->for($user, 'owner')->create(['name' => 'Personal']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('role="combobox"', false)
            ->assertSee('aria-controls="task-search-suggestions"', false)
            ->assertSee('data-task-search-suggestions-url="'.route('tasks.search-suggestions').'"', false)
            ->assertDontSee('data-dashboard-task-search', false);
    }

    public function test_task_suggestions_require_authentication(): void
    {
        $this->getJson(route('tasks.search-suggestions', ['q' => 'ri']))
            ->assertUnauthorized();
    }

    public function test_task_suggestions_reject_queries_outside_the_supported_length(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('tasks.search-suggestions', ['q' => 'r']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_task_suggestions_are_case_insensitive_rank_prefixes_and_respect_visibility(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $ownedProject = Project::factory()->for($owner, 'owner')->create(['name' => 'Personal']);
        $privateProject = Project::factory()->for($otherOwner, 'owner')->create(['name' => 'Private']);
        Task::factory()->for($ownedProject)->create([
            'title' => 'Rizal Essay',
            'due_date' => today()->addDays(10),
        ]);
        Task::factory()->for($ownedProject)->create([
            'title' => 'Notes about Rizal',
            'due_date' => today()->addDay(),
        ]);
        Task::factory()->for($privateProject)->create([
            'title' => 'Rizal Private',
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('tasks.search-suggestions', ['q' => 'rizal']));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Rizal Essay')
            ->assertJsonPath('data.1.title', 'Notes about Rizal')
            ->assertJsonMissing(['title' => 'Rizal Private']);
    }

    public function test_task_suggestions_match_adjacent_letter_typos_and_rank_the_closest_title_first(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create(['name' => 'School']);
        Task::factory()->for($project)->create(['title' => 'Google Form Quiz']);
        Task::factory()->for($project)->create(['title' => 'Google Form Quzixy']);

        $response = $this->actingAs($user)
            ->getJson(route('tasks.search-suggestions', ['q' => 'Google Form Quzi']));

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'Google Form Quiz')
            ->assertJsonPath('data.1.title', 'Google Form Quzixy');
    }

    public function test_task_suggestions_return_at_most_five_results(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->for($user, 'owner')->create();
        Task::factory()->for($project)->count(6)->create(['title' => 'Planning task']);

        $this->actingAs($user)
            ->getJson(route('tasks.search-suggestions', ['q' => 'planning']))
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }
}
