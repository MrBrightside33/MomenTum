@php
    $taskJson = json_encode(
        $project['task'],
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    );
    $statusStyles = match ($project['status']) {
        'completed' => 'bg-emerald-50 text-emerald-700',
        'planning' => 'bg-slate-100 text-slate-600',
        'archived' => 'bg-slate-100 text-slate-500',
        default => 'bg-indigo-50 text-indigo-700',
    };
    $statusLabels = [
        'completed' => 'Completed',
        'planning' => 'Planning',
        'in_progress' => 'In progress',
        'archived' => 'Archived',
    ];
    $progressColor = $project['progress'] === 100 ? 'bg-emerald-500' : 'bg-indigo-500';
@endphp

<article
    role="button"
    tabindex="0"
    aria-label="Open {{ $project['name'] }}"
    data-project-card
    data-project-open="{{ $project['id'] }}"
    data-project-task-id="{{ $project['id'] }}"
    data-project-status="{{ $project['status'] }}"
    data-project-search="{{ $project['search'] }}"
    data-project-url="{{ route('projects.show', ['task' => $project['id']]) }}"
    data-task="{{ $taskJson }}"
    class="flex h-full min-w-0 cursor-pointer flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
>
    <div class="flex items-start justify-between gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
            <x-icon name="sparkles" class="size-5" />
        </span>
        <div class="flex flex-wrap items-center justify-end gap-1.5">
            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600">High priority</span>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles }}">{{ $statusLabels[$project['status']] }}</span>
        </div>
    </div>
    <h2 class="mt-5 line-clamp-2 min-h-14 text-lg font-bold leading-7 text-slate-900">{{ $project['name'] }}</h2>
    <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
        From project
        <span class="font-semibold text-slate-700">{{ $project['parent_project'] }}</span>
    </p>

    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-3.5">
        <div class="flex items-center justify-between text-xs">
            <span class="font-medium text-slate-600">Project progress</span>
            <span class="font-semibold tabular-nums text-slate-800">{{ $project['progress'] }}%</span>
        </div>
        <div
            class="mt-2.5 h-2 overflow-hidden rounded-full bg-slate-200"
            role="progressbar"
            aria-label="{{ $project['name'] }} project progress"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $project['progress'] }}"
        >
            <div class="h-full rounded-full {{ $progressColor }} transition-all" style="width: {{ $project['progress'] }}%"></div>
        </div>
    </div>

    <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-800">{{ $project['collaboration'] }}</p>
            <div class="mt-2 flex items-center">
                @foreach (array_slice($project['members'], 0, 4) as $member)
                    <span
                        class="-ml-1.5 flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-violet-100 text-[10px] font-bold text-violet-700 first:ml-0"
                        title="{{ $member['name'] }}"
                    >{{ $member['initials'] }}</span>
                @endforeach
                <span class="ml-2 text-xs text-slate-500">{{ count($project['members']) }} {{ count($project['members']) === 1 ? 'collaborator' : 'collaborators' }}</span>
            </div>
        </div>
        <div class="flex shrink-0 flex-col items-end gap-2">
            <span class="text-xs font-medium text-slate-500">Due {{ $project['due'] }}</span>
            <button
                type="button"
                data-project-open
                class="inline-flex min-h-11 items-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            >View task</button>
        </div>
    </div>
</article>
