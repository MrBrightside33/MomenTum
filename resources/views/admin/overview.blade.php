@extends('layouts.admin')

@section('title', 'Admin overview · MomenTum')
@section('skeleton', 'admin-overview')

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

        <div
            id="admin-overview-data"
            data-lazy-region="{{ route('admin.overview.data') }}"
            data-lazy-skeleton="admin-overview-data"
            aria-busy="true"
        >
            <div class="space-y-6">
                <x-sk.stat-cards />
                <x-sk.table columns="2" rows="5" />
            </div>
        </div>
    </div>
@endsection
