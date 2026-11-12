<?php

namespace Tests\Feature\Tasks;

use Tests\TestCase;

class UpdateTaskTest extends TestCase
{
    public function test_valid_update_returns_200_and_escapes_task_text(): void
    {
        $title = 'Review <script>alert("updated")</script>';
        $description = 'Replace unsafe content: <img src=x onerror=alert(1)>';
        $response = $this->patchJson(route('tasks.update', ['taskId' => 'sample-task-1']), [
            'title' => $title,
            'description' => $description,
            'project' => 'Product Design',
            'priority' => 'High',
            'status' => 'In Progress',
            'due_date' => '2020-05-01',
            'assignees' => ['am', 'lk'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Task updated')
            ->assertJsonPath('task_id', 'sample-task-1')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($title)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $title))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($description)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $description))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'May 1'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Mark complete'));
    }

    public function test_update_rejects_invalid_task_fields_with_422(): void
    {
        $response = $this->patchJson(route('tasks.update', ['taskId' => 'sample-task-1']), [
            'title' => 'ab',
            'project' => 'Unknown',
            'priority' => 'Urgent',
            'status' => 'Started',
            'due_date' => 'not-a-date',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'project',
                'priority',
                'status',
                'due_date',
            ]);
    }
}
