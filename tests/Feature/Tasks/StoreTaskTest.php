<?php

namespace Tests\Feature\Tasks;

use Tests\TestCase;

class StoreTaskTest extends TestCase
{
    public function test_dashboard_renders_the_task_modal_and_open_triggers(): void
    {
        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Create a new task')
            ->assertSee('Delete task?')
            ->assertSee('Add Task Successfully')
            ->assertSee('View Task')
            ->assertSee('data-modal-open="create-task-modal"', false)
            ->assertSee('data-edit-task', false)
            ->assertSee('data-delete-task', false)
            ->assertSee('id="task-list"', false)
            ->assertSee('data-stat="total"', false);
    }

    public function test_valid_task_input_returns_a_rendered_row_with_escaped_title(): void
    {
        $title = '<script>alert("task")</script>';
        $response = $this->postJson(route('tasks.store'), [
            'title' => $title,
            'description' => 'Prepare the release checklist.',
            'project' => 'Personal',
            'priority' => 'High',
            'status' => 'Completed',
            'due_date' => today()->toDateString(),
            'assignees' => ['am', 'lk'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Task created')
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, e($title)))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, $title))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Today'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'AM'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'LK'))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Mark incomplete'));
    }

    public function test_future_due_date_uses_a_month_day_label_for_an_open_task(): void
    {
        $dueDate = today()->addDays(3);
        $response = $this->postJson(route('tasks.store'), [
            'title' => 'Review accessibility',
            'project' => 'Product Design',
            'priority' => 'Low',
            'status' => 'Open',
            'due_date' => $dueDate->toDateString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, $dueDate->format('M j')))
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Mark complete'))
            ->assertJsonPath('html', fn (string $html): bool => ! str_contains($html, 'AM'));
    }

    public function test_task_creation_rejects_invalid_fields_and_assignees(): void
    {
        $response = $this->postJson(route('tasks.store'), [
            'title' => 'ab',
            'description' => str_repeat('x', 501),
            'project' => 'Unknown',
            'priority' => 'Urgent',
            'status' => 'Started',
            'due_date' => today()->subDay()->toDateString(),
            'assignees' => ['unknown'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'project',
                'priority',
                'status',
                'due_date',
                'assignees.0',
            ]);
    }
}
