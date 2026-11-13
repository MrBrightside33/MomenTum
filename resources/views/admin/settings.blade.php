@extends('layouts.admin')

@section('title', 'Admin settings · MomenTum')
@section('skeleton', 'settings')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            title="Admin settings"
            subtitle="Review the current access model and workspace administration boundaries."
        />

        <section class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600">
                    <x-icon name="shield-check" class="size-5" />
                </span>
                <div>
                    <h2 class="font-semibold text-indigo-900">Read-only administrator access</h2>
                    <p class="mt-1 text-sm leading-6 text-indigo-800">
                        Admin accounts can review workspace data and audit events, but cannot create, edit, or delete user, task, or project records from this area.
                    </p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Role permissions</h2>
                <p class="mt-1 text-sm text-slate-500">Current permissions configured for this application.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach ($roles as $role => $details)
                    <article class="p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-admin.role-chip :role="$role" />
                            <span class="text-sm text-slate-500">{{ $details['description'] }}</span>
                        </div>
                        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach ($details['capabilities'] as $capability => $allowed)
                                <li class="flex items-start gap-2 text-sm text-slate-600">
                                    <span class="mt-0.5 {{ $allowed ? 'text-emerald-600' : 'text-slate-400' }}">
                                        <x-icon :name="$allowed ? 'check' : 'x'" class="size-4" />
                                    </span>
                                    <span>{{ $capability }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Personal account preferences</h2>
                <p class="mt-1 text-sm text-slate-500">Update your own profile, password, and notification settings.</p>
            </div>
            <a href="{{ route('settings') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Open personal settings
            </a>
        </section>
    </div>
@endsection
