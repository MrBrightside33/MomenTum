@php
    $badge = match ($task['priority']) {
        'High' => 'bg-red-50 text-red-600',
        'Medium' => 'bg-amber-50 text-amber-600',
        default => 'bg-emerald-50 text-emerald-600',
    };
@endphp

<div
    id="task-row-{{ $task['id'] }}"
    data-task-row
    data-task-id="{{ $task['id'] }}"
    data-task-title="{{ $task['title'] }}"
    data-task-description="{{ $task['description'] }}"
    data-task-project="{{ $task['category'] }}"
    data-task-priority="{{ $task['priority'] }}"
    data-task-status="{{ $task['status'] }}"
    data-task-due-date="{{ $task['due_date'] }}"
    data-task-assignees="{{ implode(',', $task['assignee_ids']) }}"
    class="flex items-center gap-4 border-b border-slate-100 px-5 py-4"
>
    {{-- Checkbox --}}
    @if ($task['done'])
        <button type="button" class="flex size-5 shrink-0 items-center justify-center rounded-md border border-indigo-600 bg-indigo-600 text-white" aria-label="Mark incomplete">
            <x-icon name="check" class="size-3.5" />
        </button>
    @else
        <button type="button" class="size-5 shrink-0 rounded-md border border-slate-300 bg-white" aria-label="Mark complete"></button>
    @endif

    {{-- Title and task metadata --}}
    <div class="min-w-0 flex-1 space-y-1">
        <div class="flex items-center gap-2">
            <p class="truncate text-sm font-semibold {{ $task['done'] ? 'text-slate-400 line-through' : 'text-slate-800' }}">{{ $task['title'] }}</p>
            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold leading-none {{ $badge }}">{{ $task['priority'] }}</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <span>{{ $task['category'] }}</span>
            <span>•</span>
            <span data-task-due-label class="{{ $task['due_urgent'] ? 'font-semibold text-amber-600' : '' }}">{{ $task['due'] }}</span>
        </div>
    </div>

    {{-- Assignees and row actions --}}
    <div class="flex items-center gap-2">
        <div class="flex items-center gap-1">
            @foreach ($task['assignees'] as $index => $initials)
                <span data-task-assignee data-assignee-name="{{ $task['assignee_names'][$index] }}" aria-label="{{ $task['assignee_names'][$index] }}" title="{{ $task['assignee_names'][$index] }}" class="flex size-7 items-center justify-center rounded-full border border-white bg-slate-200 text-[9px] font-bold text-slate-600">{{ $initials }}</span>
            @endforeach
        </div>

        <div class="flex items-center gap-1">
            @foreach (['eye' => 'View', 'pencil' => 'Edit', 'trash' => 'Delete'] as $icon => $label)
                @if ($icon === 'pencil')
                    <button type="button" data-edit-task aria-label="Edit {{ $task['title'] }}" class="flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                        <x-icon :name="$icon" class="size-4" />
                    </button>
                @elseif ($icon === 'trash')
                    <button type="button" data-delete-task aria-label="Delete {{ $task['title'] }}" class="flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500/40">
                        <x-icon :name="$icon" class="size-4" />
                    </button>
                @else
                    <a href="#" aria-label="{{ $label }}" class="flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50">
                        <x-icon :name="$icon" class="size-4" />
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
