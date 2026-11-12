@extends('layouts.admin')

@section('title', 'Audit log · MomenTum')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="Audit log"
            subtitle="Review authentication events and recorded workspace activity."
        >
            <x-slot:actions>
                <a href="{{ route('admin.reports.export', 'audit') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Export CSV
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-end">
                <label class="relative block w-full flex-1 lg:max-w-md">
                    <span class="sr-only">Search audit events</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Search events, descriptions, or actors" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                </label>
                <label class="block w-full sm:w-56">
                    <span class="sr-only">Filter by event</span>
                    <select name="action" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                        <option value="">All event types</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm text-slate-600">
                    <input type="checkbox" name="group" value="security" @checked(($filters['group'] ?? '') === 'security') class="size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Security events only
                </label>
                <button type="submit" class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                    Apply filters
                </button>
            </form>

            <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                <p class="text-sm font-semibold text-slate-900">Recorded events</p>
                <p class="text-xs text-slate-500">{{ number_format($auditLogs->total()) }} events</p>
            </div>

            @if ($auditLogs->count() === 0)
                <div class="border-t border-slate-100">
                    <x-admin.empty-state title="No audit events found" description="Try another search or clear the selected filters." icon="clock" />
                </div>
            @else
                <div class="divide-y divide-slate-100 border-t border-slate-100">
                    @foreach ($auditLogs as $auditLog)
                        <article class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-5">
                            <div class="flex min-w-0 gap-3">
                                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl {{ str_starts_with($auditLog->action, 'auth.') ? 'bg-amber-50 text-amber-600' : 'bg-indigo-50 text-indigo-600' }}">
                                    <x-icon :name="str_starts_with($auditLog->action, 'auth.') ? 'shield-check' : 'clock'" class="size-[18px]" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900">{{ $auditLog->description }}</p>
                                    <p class="mt-1 break-words text-xs text-slate-500">
                                        {{ $auditLog->action }}
                                        <span class="px-1">·</span>
                                        {{ $auditLog->actor?->name ?? 'System or removed account' }}
                                        @if ($auditLog->subject_type)
                                            <span class="px-1">·</span>
                                            {{ class_basename($auditLog->subject_type) }} #{{ $auditLog->subject_id }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <time class="shrink-0 pl-12 text-xs text-slate-400 sm:pl-0" datetime="{{ $auditLog->created_at?->toIso8601String() }}">
                                {{ $auditLog->created_at?->format('M j, Y · g:i A') }}
                            </time>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 p-4 sm:px-5">
                    <x-admin.pagination :paginator="$auditLogs" />
                </div>
            @endif
        </section>
    </div>
@endsection
