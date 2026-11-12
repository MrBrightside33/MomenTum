@extends('layouts.app')

@section('title', 'Overview · MomenTum')

@section('content')

    {{-- Greeting --}}
    <div class="flex items-end justify-between">
        <div>
            <p class="text-sm font-semibold uppercase text-indigo-600">{{ now()->format('l, F j') }}</p>
            <h1 class="pt-1 text-4xl font-bold tracking-tight text-slate-900">Good morning, {{ $user['name'] }}</h1>
            <p class="pt-2 text-slate-500">Here’s what’s happening with your tasks today.</p>
        </div>

        <a href="{{ route('calendar') }}" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 shadow-sm transition hover:bg-slate-50">
            <x-icon name="calendar" class="size-[17px]" />
            <span>Calendar</span>
        </a>
    </div>

    {{-- Stat cards --}}
    <section class="mt-8 grid gap-4 sm:grid-cols-2">
        @foreach ($stats as $stat)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        <p data-stat="{{ $stat['key'] }}" class="pt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $stat['value'] }}</p>
                    </div>
                    <div class="flex size-11 items-center justify-center rounded-xl {{ $stat['bg'] }} {{ $stat['fg'] }}">
                        <x-icon :name="$stat['icon']" class="size-[21px]" />
                    </div>
                </div>
                <p data-stat-note="{{ $stat['key'] }}" class="pt-4 text-xs font-medium text-slate-500">{{ $stat['note'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- My tasks --}}
    <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow">
        <div class="space-y-3 border-b border-slate-100 p-5">
            <div>
                <h2 class="text-lg font-bold text-slate-900">My tasks</h2>
                <p class="pt-1 text-sm text-slate-500">Your priorities for the day</p>
            </div>

            <div data-dashboard-task-controls class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-sm" role="group" aria-label="Filter tasks by status">
                    @foreach (['all' => 'All', 'Open' => 'Open', 'In Progress' => 'In progress', 'Completed' => 'Completed'] as $value => $label)
                        <button type="button" data-dashboard-status-filter="{{ $value }}" aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}" class="rounded-md px-3 py-1.5 {{ $value === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-sm" role="group" aria-label="Filter tasks by priority">
                    @foreach (['all' => 'All', 'High' => 'High', 'Medium' => 'Medium', 'Low' => 'Low'] as $value => $label)
                        <button type="button" data-dashboard-priority-filter="{{ $value }}" aria-pressed="{{ $value === 'all' ? 'true' : 'false' }}" class="rounded-md px-3 py-1.5 {{ $value === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <label class="relative">
                    <span class="sr-only">Sort tasks</span>
                    <select data-dashboard-task-sort class="h-10 appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-3 pr-9 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="due_date">Sort: Due date</option>
                        <option value="priority">Sort: Priority</option>
                        <option value="title">Sort: Task name</option>
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                </label>
            </div>
        </div>

        {{-- Task rows --}}
        <div id="task-list">
            @foreach ($tasks as $task)
                @include('tasks._row', ['task' => $task])
            @endforeach
            <p data-task-list-empty class="{{ $tasks->isEmpty() ? '' : 'hidden' }} px-5 py-10 text-center text-sm text-slate-500">No tasks yet. Add a task to get started.</p>
            <p data-task-filter-empty class="hidden px-5 py-10 text-center text-sm text-slate-500" role="status">No tasks match these filters.</p>
        </div>

        @can('create', \App\Models\Task::class)
            <button type="button" data-modal-open="create-task-modal" class="flex w-full items-center justify-center gap-2 p-4 text-indigo-600 hover:bg-slate-50">
                <x-icon name="plus" class="size-[17px]" />
                Add a new task
            </button>
        @endcan
    </section>

    {{-- Weekly focus --}}
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Weekly focus</h2>
                <p class="pt-1 text-sm text-slate-500">{{ $weekTitle }}</p>
            </div>
            <a href="{{ route('calendar') }}" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-50" aria-label="View calendar" title="View calendar">
                <x-icon name="calendar" class="size-[18px]" />
            </a>
        </div>

        <div class="flex h-[159px] items-end gap-2 pt-6">
            @foreach ($week as $bar)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="flex h-28 w-full items-end rounded-md bg-slate-100">
                        <div class="w-full rounded-md {{ !empty($bar['highlight']) ? 'bg-indigo-600' : 'bg-indigo-200' }}"
                             style="height: {{ $bar['value'] }}%"></div>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-400">{{ $bar['day'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex items-center justify-between rounded-xl bg-emerald-50 p-3 text-sm">
            <span class="font-medium text-emerald-800">{{ $completedThisWeek }} tasks completed</span>
            <span class="font-bold text-emerald-700">This week</span>
        </div>
    </section>

    {{-- Streak banner --}}
    <section data-streak-banner class="mt-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 p-5 text-white shadow-lg shadow-indigo-600/15">
        <div class="flex size-10 items-center justify-center rounded-xl bg-white/15">
            <x-icon name="sparkles" class="size-5" />
        </div>
        <h3 class="pt-4 text-lg font-bold">{{ $streakTitle }}</h3>
        <p data-streak-copy class="pt-2 text-sm text-indigo-100">{{ $streakCopy }}</p>
        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-white/15">
            <div data-streak-progress class="h-full rounded-full bg-white" style="width: {{ $streakProgress }}%"></div>
        </div>
    </section>

@endsection
