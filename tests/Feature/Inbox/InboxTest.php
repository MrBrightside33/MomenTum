<?php

namespace Tests\Feature\Inbox;

use Tests\TestCase;

class InboxTest extends TestCase
{
    public function test_inbox_page_renders_updates_filters_and_task_links(): void
    {
        $response = $this->get(route('inbox'));

        $response
            ->assertOk()
            ->assertSee('Inbox')
            ->assertSee('Stay on top of task assignments, mentions, and team activity.')
            ->assertSee('Lina Kim assigned you a task')
            ->assertSee('data-inbox-filter="unread"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('dashboard').'#task-row-sample-task-1', false);
    }
}
