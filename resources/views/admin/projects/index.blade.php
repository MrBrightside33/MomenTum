@extends('layouts.admin')

@section('title', 'Projects · MomenTum')
@section('skeleton', 'admin-projects')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="Projects"
            subtitle="Review workspace projects and their task distribution. Admin access is read-only."
        />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-end sm:p-5">
                <label class="relative block w-full flex-1 sm:max-w-md">
                    <span class="sr-only">Search projects</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Search project name or description" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </label>
                <label class="block w-full sm:w-48">
                    <span class="sr-only">Filter by project status</span>
                    <select name="status" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str_replace('_', ' ', ucfirst($status->value)) }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    Apply filters
                </button>
            </form>

            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                <p class="text-sm font-semibold text-slate-900">Workspace projects</p>
                <p class="text-xs text-slate-500">{{ number_format($projects->total()) }} projects</p>
            </div>

            @if ($projects->count() === 0)
                <div class="border-t border-slate-100">
                    <x-admin.empty-state title="No projects found" description="Try a different search or remove the selected status filter." icon="folder" />
                </div>
            @else
                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="w-full min-w-[720px] text-left">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">Project</th>
                                <th scope="col" class="px-4 py-3">Owner</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right">Tasks</th>
                                <th scope="col" class="px-5 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($projects as $project)
                                <tr class="hover:bg-slate-50/70">
                                    <th scope="row" class="max-w-[360px] px-5 py-4 font-normal">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $project->name }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $project->description ?: 'No description' }}</p>
                                    </th>
                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $project->owner?->name ?? 'Deleted user' }}</td>
                                    <td class="px-4 py-4">
                                        <x-admin.status-badge :status="$project->effective_status" />
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm tabular-nums text-slate-600">{{ number_format($project->tasks_count) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">{{ $project->created_at?->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-4 sm:px-5">
                    <x-admin.pagination :paginator="$projects" />
                </div>
            @endif
        </section>
    </div>
@endsection
