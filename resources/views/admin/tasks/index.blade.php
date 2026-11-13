@extends('layouts.admin')

@section('title', 'All tasks · MomenTum')
@section('skeleton', 'admin-tasks')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="All tasks"
            subtitle="Review task activity across the workspace. Admin access is read-only."
        />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.tasks.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-end">
                <label class="relative block w-full flex-1 lg:max-w-md">
                    <span class="sr-only">Search tasks</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Search task, project, or creator" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </label>
                <label class="block w-full sm:w-44">
                    <span class="sr-only">Filter by status</span>
                    <select name="status" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block w-full sm:w-44">
                    <span class="sr-only">Filter by priority</span>
                    <select name="priority" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">All priorities</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->value }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    Apply filters
                </button>
            </form>

            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                <p class="text-sm font-semibold text-slate-900">Workspace tasks</p>
                <p class="text-xs text-slate-500">{{ number_format($tasks->total()) }} tasks</p>
            </div>

            @if ($tasks->count() === 0)
                <div class="border-t border-slate-100">
                    <x-admin.empty-state title="No tasks found" description="Try a different search or remove one of the selected filters." icon="list-checks" />
                </div>
            @else
                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="w-full min-w-[900px] text-left">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Task</th>
                                <th scope="col" class="px-4 py-3">Project</th>
                                <th scope="col" class="px-4 py-3">Creator</th>
                                <th scope="col" class="px-4 py-3">Assignees</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Priority</th>
                                <th scope="col" class="px-5 py-3">Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($tasks as $task)
                                <tr class="hover:bg-slate-50/70">
                                    <th scope="row" class="max-w-[280px] px-5 py-4 font-normal">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $task->title }}</p>
                                        <p class="mt-1 text-xs text-slate-500">#{{ $task->getKey() }}</p>
                                    </th>
                                    <td class="max-w-40 truncate px-4 py-4 text-sm text-slate-600">{{ $task->project?->name ?? 'Unknown project' }}</td>
                                    <td class="max-w-40 truncate px-4 py-4 text-sm text-slate-600">{{ $task->creator?->name ?? 'Deleted user' }}</td>
                                    <td class="max-w-48 truncate px-4 py-4 text-sm text-slate-600">
                                        {{ $task->assignees->pluck('name')->join(', ') ?: 'Unassigned' }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-admin.status-badge :status="strtolower(str_replace(' ', '_', $task->status->value))" />
                                    </td>
                                    <td class="px-4 py-4 text-sm font-medium text-slate-600">{{ $task->priority->value }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-4 sm:px-5">
                    <x-admin.pagination :paginator="$tasks" />
                </div>
            @endif
        </section>
    </div>
@endsection
