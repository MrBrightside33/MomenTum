<?php

namespace Tests\Feature\Tasks;

use Tests\TestCase;

class DeleteTaskTest extends TestCase
{
    public function test_delete_endpoint_returns_the_deleted_task_identifier(): void
    {
        $response = $this->deleteJson(route('tasks.destroy', ['taskId' => 'sample-task-1']));

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Task deleted')
            ->assertJsonPath('task_id', 'sample-task-1');
    }
}
