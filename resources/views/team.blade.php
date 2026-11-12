@extends('layouts.app')

@section('title', 'Team · MomenTum')

@section('content')
    <div data-team-root class="space-y-6">
        <section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Your workspace</p>
                <h1 class="mt-1 text-4xl font-bold tracking-tight text-slate-900">Team</h1>
                <p class="mt-2 text-slate-500">See who's on your team and how work is distributed.</p>
            </div>
            <a
                href="{{ route('projects') }}"
                class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 sm:self-auto"
            >
                <x-icon name="folder" class="size-4 text-indigo-600" />
                View projects
            </a>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Team overview">
            @foreach ($teamStats as $stat)
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

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Team members">
            <div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <label class="relative block w-full lg:max-w-sm">
                    <span class="sr-only">Search team members</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        data-team-search
                        placeholder="Search team members or projects"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </label>

                <div class="flex w-full items-center gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 text-sm sm:w-auto" role="group" aria-label="Filter team members">
                    @foreach (['all' => 'Everyone', 'owner' => 'Owner', 'member' => 'Members'] as $filter => $label)
                        <button
                            type="button"
                            data-team-filter="{{ $filter }}"
                            aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"
                            class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg px-3 text-sm font-medium transition {{ $filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}"
                        >
                            {{ $label }}
                            <span data-team-filter-count="{{ $filter }}" class="rounded-full bg-slate-200/70 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-500">{{ $teamCounts[$filter] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div data-team-grid class="grid gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($teamMembers as $index => $member)
                    @php
                        $avatarColors = ['bg-indigo-100 text-indigo-700', 'bg-violet-100 text-violet-700', 'bg-emerald-100 text-emerald-700', 'bg-amber-100 text-amber-700'];
                        $roleFilter = strtolower($member['role']);
                    @endphp
                    <article
                        data-team-card
                        data-team-role="{{ $roleFilter }}"
                        data-team-search="{{ $member['search'] }}"
                        class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:shadow-md sm:p-5"
                    >
                        <div class="flex items-center gap-3">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl text-sm font-bold {{ $avatarColors[$index % count($avatarColors)] }}" aria-hidden="true">
                                {{ $member['initials'] }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-base font-bold text-slate-900">{{ $member['name'] }}</h2>
                                <p class="mt-0.5 text-sm text-slate-500">{{ $member['role'] }}</p>
                            </div>
                            @if ($member['role'] === 'Owner')
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">Owner</span>
                            @else
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Active</span>
                            @endif
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3 rounded-xl bg-slate-50 p-3">
                            <div>
                                <p class="text-xs font-medium text-slate-500">Assigned tasks</p>
                                <p class="mt-1 text-lg font-bold tabular-nums text-slate-900">{{ $member['task_count'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-slate-500">Completed</p>
                                <p class="mt-1 text-lg font-bold tabular-nums text-slate-900">{{ $member['completed_task_count'] }}</p>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Projects</h3>
                            @if (count($member['projects']) > 0)
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($member['projects'] as $project)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ $project }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="mt-2 text-sm text-slate-400">No assigned projects</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div data-team-empty class="hidden flex-col items-center px-6 py-14 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <x-icon name="users" class="size-6" />
                </span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">No team members found</h2>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Try another search or clear the current filter.</p>
                <button type="button" data-team-reset class="mt-4 text-sm font-semibold text-indigo-600 hover:text-indigo-700 focus:outline-none focus:underline">Clear filters</button>
            </div>
        </section>

        <p data-team-result-count class="sr-only" role="status" aria-live="polite"></p>
    </div>
@endsection
