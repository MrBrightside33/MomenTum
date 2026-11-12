<?php

namespace Tests\Feature\Projects;

use Tests\TestCase;

class ProjectsTest extends TestCase
{
    public function test_projects_page_shows_project_summaries_and_task_links(): void
    {
        $response = $this->get(route('projects'));

        $response
            ->assertOk()
            ->assertSee('Projects')
            ->assertSee('Product Design')
            ->assertSee('Development')
            ->assertSee('data-project-filter="in_progress"', false)
            ->assertSee('data-project-search', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('dashboard').'#task-row-sample-task-1', false);
    }
}
