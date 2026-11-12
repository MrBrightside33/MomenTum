@php
    $projects = config('tasks.projects');
    $priorities = config('tasks.priorities');
    $statuses = config('tasks.statuses');
    $team = config('tasks.team');
    $selectedAssignees = array_filter($team, fn (array $member): bool => $member['id'] === 'am');
@endphp

<x-modal id="create-task-modal" title="Create a new task" subtitle="Add a clear, actionable item to your list.">
    {{-- Task create and edit form --}}
    <form
        id="create-task-form"
        action="{{ route('tasks.store') }}"
        method="POST"
        data-create-action="{{ route('tasks.store') }}"
        data-update-url-template="{{ route('tasks.update', ['taskId' => '__task_id__']) }}"
        novalidate
        class="flex flex-col gap-4"
    >
        @csrf
        <input id="task-method-override" type="hidden" name="_method" value="">

        {{-- Task name --}}
        <div class="flex flex-col gap-2">
            <div class="flex h-7 items-center gap-1.5">
                <label for="title" class="text-sm font-semibold text-slate-700">Task name</label>
                <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-red-600">Required</span>
                <span id="title-counter" class="ml-auto text-xs tabular-nums text-slate-400" data-create-counter>0/120</span>
            </div>
            <input
                id="title"
                name="title"
                type="text"
                maxlength="120"
                autocomplete="off"
                placeholder="What needs to be done?"
                data-autofocus
                aria-describedby="title-help"
                class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-base text-slate-900 placeholder:text-slate-900/50 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            >
            <p id="title-help" class="text-xs leading-4 text-slate-500">Use a short, actionable title that teammates can scan quickly.</p>
            <p id="title-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
        </div>

        {{-- Task description --}}
        <div class="flex flex-col gap-2">
            <div class="flex h-7 items-center gap-1.5">
                <label for="description" class="text-sm font-semibold text-slate-700">Description</label>
                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-slate-500">Optional</span>
                <span id="description-counter" class="ml-auto text-xs tabular-nums text-slate-400" data-create-counter>0/500</span>
            </div>
            <textarea
                id="description"
                name="description"
                rows="3"
                maxlength="500"
                placeholder="Add context, blockers, or acceptance criteria so the task is easy to understand."
                aria-describedby="description-help"
                class="h-24 w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-base leading-6 text-slate-900 placeholder:text-slate-900/50 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            ></textarea>
            <p id="description-help" class="text-xs leading-4 text-slate-500">Keep it concise and focused on what success looks like.</p>
            <p id="description-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
        </div>

        {{-- Project and priority --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <div class="flex h-7 items-center gap-1.5">
                    <label for="project" class="text-sm font-semibold text-slate-700">Project</label>
                    <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-red-600">Required</span>
                </div>
                <div class="relative">
                    <select id="project" name="project" aria-describedby="project-help" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @foreach ($projects as $project)
                            <option value="{{ $project }}" @selected($project === 'Personal')>{{ $project }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                </div>
                <p id="project-help" class="text-xs leading-4 text-slate-500">Choose the project this task belongs to.</p>
                <p id="project-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex h-7 items-center gap-1.5">
                    <label for="priority" class="text-sm font-semibold text-slate-700">Priority</label>
                    <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-red-600">Required</span>
                </div>
                <div class="relative">
                    <select id="priority" name="priority" aria-describedby="priority-help" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected($priority === 'Medium')>{{ $priority }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                </div>
                <p id="priority-help" class="text-xs leading-4 text-slate-500">Use High for urgent work and Low for routine follow-up.</p>
                <p id="priority-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
            </div>
        </div>

        {{-- Status and due date --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <div class="flex h-7 items-center gap-1.5">
                    <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                    <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-red-600">Required</span>
                </div>
                <div class="relative">
                    <select id="status" name="status" aria-describedby="status-help" class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($status === 'Open')>{{ $status }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-4 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                </div>
                <p id="status-help" class="text-xs leading-4 text-slate-500">New tasks default to Open until they are marked as In Progress.</p>
                <p id="status-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex h-7 items-center gap-1.5">
                    <label for="due_date" class="text-sm font-semibold text-slate-700">Due date</label>
                    <span class="rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-red-600">Required</span>
                </div>
                <input id="due_date" name="due_date" type="date" min="{{ now()->toDateString() }}" aria-describedby="due_date-help" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-base text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <div class="flex flex-wrap gap-2" role="group" aria-label="Quick due dates">
                    @foreach ([0 => 'Today', 1 => 'Tomorrow', 7 => 'Next week'] as $offset => $label)
                        <button type="button" data-quick-date="{{ $offset }}" aria-pressed="false" class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <p id="due_date-help" class="text-xs leading-4 text-slate-500">Use a realistic deadline so teammates can plan their work.</p>
                <p id="due_date-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
            </div>
        </div>

        {{-- Assignee picker --}}
        <div class="flex flex-col gap-2">
            <div class="flex h-7 items-center gap-1.5">
                <label for="assignee-toggle" class="text-sm font-semibold text-slate-700">Assignees</label>
                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold leading-3 text-slate-500">Optional</span>
            </div>

            <div class="relative">
                <div id="assignee-box" class="flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4">
                    <div id="assignee-avatars" class="flex items-center gap-2">
                        @foreach ($selectedAssignees as $member)
                            <button
                                type="button"
                                data-assignee-remove="{{ $member['id'] }}"
                                aria-label="Remove {{ $member['name'] }}"
                                title="{{ $member['name'] }} (click to remove)"
                                class="flex size-7 items-center justify-center rounded-full border border-white bg-slate-200 text-[9px] font-bold text-slate-600 hover:bg-red-100 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                            >
                                {{ $member['initials'] }}
                            </button>
                        @endforeach
                    </div>
                    <button
                        id="assignee-toggle"
                        type="button"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                        aria-label="Add assignee"
                        aria-describedby="assignees-help"
                        class="flex size-7 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-sm font-semibold text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    >+</button>
                </div>

                <div id="assignee-hidden-inputs" class="hidden">
                    @foreach ($selectedAssignees as $member)
                        <input type="hidden" name="assignees[]" value="{{ $member['id'] }}" data-assignee-hidden>
                    @endforeach
                </div>

                <div id="assignee-popover" role="listbox" aria-label="Select assignees" aria-multiselectable="true" hidden class="absolute left-0 top-full z-10 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                    @foreach ($team as $member)
                        <label
                            data-assignee-option
                            data-assignee-id="{{ $member['id'] }}"
                            data-assignee-initials="{{ $member['initials'] }}"
                            data-assignee-name="{{ $member['name'] }}"
                            role="option"
                            aria-selected="{{ $member['id'] === 'am' ? 'true' : 'false' }}"
                            class="flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2 hover:bg-slate-50"
                        >
                            <input type="checkbox" value="{{ $member['id'] }}" data-assignee-checkbox @checked($member['id'] === 'am') class="size-4 rounded accent-indigo-600">
                            <span class="flex size-7 items-center justify-center rounded-full bg-slate-200 text-[9px] font-bold text-slate-600">{{ $member['initials'] }}</span>
                            <span class="text-sm text-slate-700">{{ $member['name'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <p id="assignees-help" class="text-xs leading-4 text-slate-500">Add teammates who should receive updates and reminders.</p>
            <p id="assignees-error" class="hidden text-xs font-medium text-red-600" role="alert"></p>
        </div>

        {{-- Task creation information --}}
        <div class="flex items-center gap-2 rounded-xl bg-indigo-50 p-3">
            <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">i</span>
            <p id="task-form-note" class="text-xs leading-4 text-indigo-600">Task name, project, priority, status, and due date are required before you can create the task.</p>
        </div>

        {{-- Modal actions --}}
        <div class="flex justify-end gap-3 pt-2">
            <button type="button" data-modal-close class="h-11 rounded-xl border border-slate-200 px-4 text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                Cancel
            </button>
            <button type="submit" id="create-task-submit" class="h-11 rounded-xl bg-indigo-600 px-5 text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                <span id="create-task-submit-label">Create task</span>
            </button>
        </div>
    </form>
</x-modal>
