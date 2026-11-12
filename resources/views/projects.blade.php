@extends('layouts.app')

@section('title', 'Projects · MomenTum')

@section('content')
    <div data-projects-root class="space-y-6">
        {{-- Projects page heading --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Your workspace</p>
                <h1 class="mt-1 text-4xl font-bold tracking-tight text-slate-900">Projects</h1>
                <p class="mt-2 text-slate-500">See how your work is moving across every project.</p>
            </div>
            <a
                href="{{ route('dashboard') }}"
                class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 sm:self-auto"
            >
                <x-icon name="list-checks" class="size-4 text-indigo-600" />
                View all tasks
            </a>
        </section>

        {{-- Project overview statistics --}}
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Project overview">
            @foreach ($projectStats as $stat)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                            <p class="pt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                        </div>
                        <span class="flex size-11 items-center justify-center rounded-xl {{ $stat['bg'] }} {{ $stat['fg'] }}">
                            <x-icon :name="$stat['icon']" class="size-[21px]" />
                        </span>
                    </div>
                    <p class="pt-4 text-xs font-medium text-slate-500">{{ $stat['note'] }}</p>
                </div>
            @endforeach
        </section>

        {{-- Search and project status filters --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <label class="relative block w-full lg:max-w-sm">
                    <span class="sr-only">Search projects</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        data-project-search
                        placeholder="Search projects or tasks"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </label>

                <div class="flex w-full items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 text-sm sm:w-auto" role="group" aria-label="Filter projects">
                    @foreach (['all' => 'All projects', 'in_progress' => 'In progress', 'completed' => 'Completed', 'planning' => 'Planning'] as $filter => $label)
                        <button
                            type="button"
                            data-project-filter="{{ $filter }}"
                            aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"
                            class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-sm font-medium transition {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}"
                        >
                            {{ $label }}
                            <span data-project-filter-count="{{ $filter }}" class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">{{ $projectCounts[$filter] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Project cards --}}
            <div data-project-grid class="grid gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    @php
                        $statusStyles = match ($project['status']) {
                            'completed' => 'bg-emerald-50 text-emerald-700',
                            'planning' => 'bg-slate-100 text-slate-600',
                            default => 'bg-indigo-50 text-indigo-700',
                        };
                        $statusLabels = [
                            'completed' => 'Completed',
                            'planning' => 'Planning',
                            'in_progress' => 'In progress',
                        ];
                    @endphp
                    <article
                        data-project-card
                        data-project-status="{{ $project['status'] }}"
                        data-project-search="{{ $project['search'] }}"
                        class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:shadow-md sm:p-5"
                    >
                        {{-- Project identity and status --}}
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                <x-icon name="folder" class="size-[21px]" />
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyles }}">{{ $statusLabels[$project['status']] }}</span>
                        </div>
                        <h2 class="mt-4 truncate text-lg font-bold text-slate-900">{{ $project['name'] }}</h2>
                        <p class="mt-1 min-h-10 text-sm leading-5 text-slate-500">{{ $project['description'] }}</p>

                        {{-- Project completion progress --}}
                        <div class="mt-5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-slate-600">Task progress</span>
                                <span class="font-semibold tabular-nums text-slate-800">{{ $project['progress'] }}%</span>
                            </div>
                            <div
                                class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"
                                role="progressbar"
                                aria-label="{{ $project['name'] }} task progress"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $project['progress'] }}"
                            >
                                <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $project['progress'] }}%"></div>
                            </div>
                        </div>

                        {{-- Project task previews --}}
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tasks</h3>
                                <span class="text-xs font-medium text-slate-500">{{ $project['completed_task_count'] }}/{{ $project['task_count'] }} done</span>
                            </div>
                            @if (count($project['tasks']) > 0)
                                <ul class="mt-2 space-y-1">
                                    @foreach (array_slice($project['tasks'], 0, 2) as $task)
                                        <li>
                                            <a href="{{ $task['url'] }}" class="flex min-w-0 items-center gap-2 rounded-lg px-2 py-2 text-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                                                <span class="size-2 shrink-0 rounded-full {{ $task['done'] ? 'bg-emerald-500' : 'bg-indigo-500' }}" aria-hidden="true"></span>
                                                <span class="min-w-0 flex-1 truncate font-medium text-slate-700">{{ $task['title'] }}</span>
                                                <span class="shrink-0 text-xs text-slate-400">{{ $task['due'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                @if (count($project['tasks']) > 2)
                                    <p class="mt-1 px-2 text-xs text-slate-400">and {{ count($project['tasks']) - 2 }} more tasks</p>
                                @endif
                            @else
                                <p class="mt-3 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-500">No tasks in this project yet.</p>
                            @endif
                        </div>

                        {{-- Project team and next deadline --}}
                        <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <div class="flex min-w-0 items-center">
                                @forelse (array_slice($project['members'], 0, 3) as $member)
                                    <span
                                        class="-ml-1.5 flex size-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-violet-100 text-[10px] font-bold text-violet-700 first:ml-0"
                                        title="{{ $member['name'] }}"
                                    >{{ $member['initials'] }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">No team members</span>
                                @endforelse
                                @if (count($project['members']) > 3)
                                    <span class="-ml-1.5 flex size-8 items-center justify-center rounded-full border-2 border-white bg-slate-100 text-[10px] font-semibold text-slate-600">+{{ count($project['members']) - 3 }}</span>
                                @endif
                            </div>
                            <p class="truncate text-right text-xs text-slate-500">
                                @if ($project['next_deadline'])
                                    Next: <span class="font-semibold text-slate-700">{{ $project['next_deadline'] }}</span>
                                @else
                                    No upcoming deadline
                                @endif
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- Empty result state --}}
            <div data-projects-empty class="hidden flex-col items-center px-6 py-14 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <x-icon name="folder" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">No matching projects</h2>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Try another search or clear the current filters.</p>
                <button type="button" data-projects-reset class="mt-4 text-sm font-semibold text-indigo-600 hover:text-indigo-700 focus:outline-none focus:underline">Clear filters</button>
            </div>
        </section>

        <p data-projects-result-count class="sr-only" role="status" aria-live="polite"></p>
    </div>
@endsection
