@extends('layouts.admin')

@section('title', 'Admin overview · MomenTum')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="Admin overview"
            subtitle="Review workspace administration, access, and activity."
        />

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <x-icon name="shield-check" class="size-7" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-slate-900">Read-only workspace administration</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-500">
                        Use the administration tools to review people, work, and security activity. Administrative access does not grant permission to modify workspace records.
                    </p>
                </div>
                <a
                    href="{{ route('admin.users.index') }}"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                >
                    Browse users
                    <x-icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card
                label="Workspace members"
                :value="number_format($adminMemberCount)"
                :note="number_format($userCounts['active']).' active accounts'"
                icon="users"
            />
            <x-admin.stat-card
                label="Failed sign-ins"
                :value="number_format($failedSignInCount)"
                note="Security events in the last 24 hours"
                icon="shield-check"
                tone="amber"
            />
            <x-admin.stat-card
                label="Open tasks"
                :value="number_format($taskCounts['Open'] ?? 0)"
                note="Tasks not yet completed"
                icon="list-checks"
                tone="violet"
            />
            <x-admin.stat-card
                label="Projects in progress"
                :value="number_format($projectCounts['in_progress'] ?? 0)"
                note="Active workspace projects"
                icon="folder"
                tone="emerald"
            />
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Recent activity</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Latest recorded workspace events</p>
                </div>
                <a href="{{ route('admin.audit.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View audit log</a>
            </div>

            @if ($recentActivity->isEmpty())
                <x-admin.empty-state
                    title="No activity recorded yet"
                    description="Authentication and workspace events will appear here."
                    icon="clock"
                />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($recentActivity as $activity)
                        <li class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ $activity->description }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $activity->actor?->name ?? 'System or removed account' }} · {{ $activity->action }}</p>
                            </div>
                            <time class="shrink-0 text-xs text-slate-400" datetime="{{ $activity->created_at?->toIso8601String() }}">
                                {{ $activity->created_at?->diffForHumans() }}
                            </time>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
