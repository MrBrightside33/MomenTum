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

        <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-700 shadow-sm hover:bg-slate-50">
            <x-icon name="calendar" class="size-[17px]" />
            <span>This week</span>
            <x-icon name="chevron-down" class="size-[15px]" />
        </button>
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

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex h-9 w-[220px] items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3">
                    <x-icon name="search" class="size-4 text-slate-400" />
                    <input type="text" placeholder="Search tasks" class="w-full bg-transparent text-sm placeholder:text-slate-400 focus:outline-none">
                </div>

                {{-- Status filter (first option = selected) --}}
                <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-sm">
                    @foreach (['All', 'Open', 'Completed'] as $i => $label)
                        <button type="button" class="rounded-md px-3 py-1.5 {{ $i === 0 ? 'bg-white text-slate-900' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Priority filter --}}
                <div class="flex items-center gap-1 rounded-lg bg-slate-100 p-1 text-sm">
                    @foreach (['All', 'High', 'Medium', 'Low'] as $i => $label)
                        <button type="button" class="rounded-md px-3 py-1.5 {{ $i === 0 ? 'bg-white text-slate-900' : 'text-slate-500 hover:text-slate-900' }}">{{ $label }}</button>
                    @endforeach
                </div>

                <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900">
                    Sort: Due date
                    <x-icon name="chevron-down" class="size-4" />
                </button>
            </div>
        </div>

        {{-- Task rows --}}
        <div id="task-list">
            @foreach ($tasks as $task)
                @include('tasks._row', ['task' => $task])
            @endforeach
        </div>

        <button type="button" data-modal-open="create-task-modal" class="flex w-full items-center justify-center gap-2 p-4 text-indigo-600 hover:bg-slate-50">
            <x-icon name="plus" class="size-[17px]" />
            Add a new task
        </button>
    </section>

    {{-- Weekly focus --}}
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Weekly focus</h2>
                <p class="pt-1 text-sm text-slate-500">Sep 23 – Sep 29</p>
            </div>
            <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50" aria-label="More">
                <x-icon name="ellipsis" class="size-[18px]" />
            </button>
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
            <span class="font-medium text-emerald-800">24 tasks completed</span>
            <span class="font-bold text-emerald-700">+12%</span>
        </div>
    </section>

    {{-- Streak banner --}}
    <section data-streak-banner class="mt-6 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 p-5 text-white shadow-lg shadow-indigo-600/15">
        <div class="flex size-10 items-center justify-center rounded-xl bg-white/15">
            <x-icon name="sparkles" class="size-5" />
        </div>
        <h3 class="pt-4 text-lg font-bold">You’re on a 7-day streak</h3>
        <p data-streak-copy class="pt-2 text-sm text-indigo-100">Keep the momentum going. Complete one more task today.</p>
        <div class="mt-5 h-1.5 overflow-hidden rounded-full bg-white/15">
            <div data-streak-progress class="h-full w-[80%] rounded-full bg-white"></div>
        </div>
    </section>

@endsection
