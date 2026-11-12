<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('update', $task) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'project' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->where(function (QueryBuilder $query) use ($user): void {
                    if (! $user->isManager()) {
                        $query->where('owner_id', $user->getKey());
                    }
                }),
            ],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'due_date' => ['required', 'date'],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => [
                'integer',
                Rule::exists('users', 'id')->where(
                    fn (QueryBuilder $query): QueryBuilder => $query->where('is_active', true),
                ),
            ],
        ];
    }
}
