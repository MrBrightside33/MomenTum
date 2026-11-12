@extends('layouts.admin')

@section('title', 'Search results · MomenTum')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            eyebrow="WORKSPACE SEARCH"
            title="Search results"
            :subtitle="$query === '' ? 'Search users, tasks, and projects across the workspace.' : 'Results for “'.$query.'”.'"
        />

        @if ($query === '')
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <x-admin.empty-state title="Enter a search term" description="Use the search field above to find workspace users, tasks, and projects." icon="search" />
            </section>
        @elseif ($users->isEmpty() && $tasks->isEmpty() && $projects->isEmpty())
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <x-admin.empty-state title="No results found" description="Try another name, email, task title, or project keyword." icon="search" />
            </section>
        @else
            <div class="space-y-5">
                @if ($users->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <h2 class="font-semibold text-slate-900">Users <span class="ml-1 text-sm font-medium text-slate-400">({{ $users->count() }})</span></h2>
                            <a href="{{ route('admin.users.index', ['q' => $query]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <li>
                                    <a href="{{ route('admin.users.index', ['q' => $user->email]) }}" class="flex items-center gap-3 px-5 py-4 transition hover:bg-slate-50">
                                        <x-admin.avatar :user="$user" />
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-slate-900">{{ $user->name }}</span>
                                            <span class="block truncate text-xs text-slate-500">{{ $user->email }}</span>
                                        </span>
                                        <x-admin.role-chip :role="$user->role" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($tasks->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <h2 class="font-semibold text-slate-900">Tasks <span class="ml-1 text-sm font-medium text-slate-400">({{ $tasks->count() }})</span></h2>
                            <a href="{{ route('admin.tasks.index', ['q' => $query]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($tasks as $task)
                                <li class="flex items-center gap-3 px-5 py-4">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                                        <x-icon name="list-checks" class="size-[18px]" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $task->title }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $task->project?->name ?? 'Unknown project' }} · {{ $task->creator?->name ?? 'Deleted user' }}</span>
                                    </span>
                                    <x-admin.status-badge :status="strtolower(str_replace(' ', '_', $task->status->value))" />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($projects->isNotEmpty())
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <h2 class="font-semibold text-slate-900">Projects <span class="ml-1 text-sm font-medium text-slate-400">({{ $projects->count() }})</span></h2>
                            <a href="{{ route('admin.projects.index', ['q' => $query]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View all</a>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @foreach ($projects as $project)
                                <li class="flex items-center gap-3 px-5 py-4">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <x-icon name="folder" class="size-[18px]" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-slate-900">{{ $project->name }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $project->owner?->name ?? 'Deleted user' }}</span>
                                    </span>
                                    <x-admin.status-badge :status="$project->effective_status" />
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection
