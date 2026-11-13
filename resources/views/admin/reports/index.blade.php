@extends('layouts.admin')

@section('title', 'Reports · MomenTum')
@section('skeleton', 'admin-reports')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="Reports"
            subtitle="Workspace account, task, and project activity at a glance."
        >
            <x-slot:actions>
                <a href="{{ route('admin.reports.export', 'users') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Export CSV
                </a>
            </x-slot:actions>
        </x-admin.page-header>

        <div
            id="admin-reports-data"
            data-lazy-region="{{ route('admin.reports.data') }}"
            data-lazy-skeleton="admin-reports"
            aria-busy="true"
        >
            <x-sk.report-charts />
        </div>
    </div>
@endsection
