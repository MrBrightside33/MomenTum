<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by_id' => fn (array $attributes): int => Project::query()
                ->findOrFail($attributes['project_id'])
                ->owner_id,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Open,
            'due_date' => today()->addDays(fake()->numberBetween(1, 30)),
            'completed_at' => null,
        ];
    }
}
