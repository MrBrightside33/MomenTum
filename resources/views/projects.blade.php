@extends('layouts.app')

@section('title', 'Projects · MomenTum')
@section('skeleton', 'projects')

@section('content')
    <div data-projects-root class="space-y-6">
        {{-- Projects page heading --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Your workspace</p>
                <h1 class="mt-1 text-4xl font-bold tracking-tight text-slate-900">Projects</h1>
                <p class="mt-2 text-slate-500">Each high-priority task is tracked as its own project.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row">
                @if ($canCreateProjects)
                    <form method="POST" action="{{ route('projects.store') }}" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <label class="sr-only" for="new-project-name">New project name</label>
                        <input
                            id="new-project-name"
                            name="name"
                            value="{{ old('name') }}"
                            maxlength="100"
                            placeholder="New project name"
                            class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 sm:w-48"
                        >
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                            <x-icon name="plus" class="size-4" />
                            New project
                        </button>
                    </form>
                @endif
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                >
                    <x-icon name="list-checks" class="size-4 text-indigo-600" />
                    View all tasks
                </a>
            </div>
        </section>
        @if (session('status'))
            <p role="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</p>
        @endif
        @error('name')
            <p role="alert" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</p>
        @enderror

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
            <div id="project-tabs" data-refresh class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <label class="relative block w-full lg:max-w-sm">
                    <span class="sr-only">Search high-priority projects</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        data-project-search
                        placeholder="Search high-priority projects"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </label>

                <div class="flex w-full items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 text-sm sm:w-auto" role="group" aria-label="Filter high-priority projects">
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
            <div id="projects-grid" data-project-grid data-refresh class="grid items-stretch gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($projects as $project)
                    @include('projects._card', ['project' => $project])
                @endforeach
            </div>

            {{-- Empty result state --}}
            <div data-projects-empty class="hidden flex-col items-center px-6 py-14 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <x-icon name="folder" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">No high-priority projects found</h2>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Mark a task as High priority to show it here as its own project.</p>
                <button type="button" data-projects-reset class="mt-4 text-sm font-semibold text-indigo-600 hover:text-indigo-700 focus:outline-none focus:underline">Clear filters</button>
            </div>
        </section>

        <p data-projects-result-count class="sr-only" role="status" aria-live="polite"></p>
    </div>

    @include('projects._project-modal')

    @if ($openedTask !== null)
        <script type="application/json" id="project-modal-data">@json($openedTask, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    @endif
@endsection
