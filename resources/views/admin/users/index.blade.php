@extends('layouts.admin')

@section('title', 'Users & roles · MomenTum')
@section('skeleton', 'admin-users')

@section('content')
    <div class="space-y-8">
        <x-admin.page-header
            eyebrow="ACCESS MANAGEMENT"
            title="Users & roles"
            subtitle="Review workspace accounts, their access roles, and recent activity."
        />

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5 lg:flex-row lg:items-end">
                <label class="relative block w-full flex-1 lg:max-w-md">
                    <span class="sr-only">Search users</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        maxlength="100"
                        placeholder="Search name, email, or job title"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                </label>

                <label class="block w-full sm:w-44">
                    <span class="sr-only">Filter by role</span>
                    <select
                        name="role"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>
                                {{ config("permissions.{$role->value}.label") }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block w-full sm:w-44">
                    <span class="sr-only">Filter by account status</span>
                    <select
                        name="status"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    >
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Invited</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    </select>
                </label>

                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 sm:flex-none">
                        Apply filters
                    </button>
                    @if (count(array_filter($filters, static fn ($value): bool => $value !== null && $value !== '')) > 0)
                        <a href="{{ route('admin.users.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-5">
                <p class="text-sm font-semibold text-slate-900">Workspace directory</p>
                <p class="text-xs text-slate-500">
                    @if ($users->total() === 1)
                        1 account
                    @else
                        {{ number_format($users->total()) }} accounts
                    @endif
                </p>
            </div>

            @if ($users->count() === 0)
                <div class="border-t border-slate-100">
                    <x-admin.empty-state
                        title="No users found"
                        description="Try a different search or remove one of the selected filters."
                        icon="users"
                    />
                </div>
            @else
                <div class="overflow-x-auto border-t border-slate-100">
                    <table class="w-full min-w-[780px] text-left">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">User</th>
                                <th scope="col" class="px-4 py-3">Role</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Last active</th>
                                <th scope="col" class="px-4 py-3 text-right">Tasks</th>
                                <th scope="col" class="px-5 py-3 text-right">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $user)
                                <tr class="transition hover:bg-slate-50/70">
                                    <th scope="row" class="px-5 py-4 font-normal">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <x-admin.avatar :user="$user" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                    </th>
                                    <td class="px-4 py-4">
                                        <x-admin.role-chip :role="$user->role" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-admin.status-badge :status="$user->accountStatus()" />
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $user->last_active_label }}</td>
                                    <td class="px-4 py-4 text-right text-sm tabular-nums text-slate-600">{{ number_format($user->assigned_tasks_count) }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <button
                                            type="button"
                                            data-modal-open="admin-user-details-modal"
                                            data-user-details-url="{{ route('admin.users.show', $user) }}"
                                            class="inline-flex h-9 items-center justify-center rounded-lg px-3 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                                            aria-label="View details for {{ $user->name }}"
                                        >
                                            View
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-4 sm:px-5">
                    <x-admin.pagination :paginator="$users" />
                </div>
            @endif
        </section>
    </div>

    <x-modal
        id="admin-user-details-modal"
        title="User details"
        subtitle="Read-only account information."
        maxWidth="max-w-[640px]"
    >
        <p data-user-details-status class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500" role="status" aria-live="polite">
            Choose a user to load their account details.
        </p>
        <div data-user-details-content class="hidden">
            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</dt>
                    <dd data-user-detail="email" class="mt-1 break-all text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Role</dt>
                    <dd data-user-detail="role" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account status</dt>
                    <dd data-user-detail="status" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Job title</dt>
                    <dd data-user-detail="job_title" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Joined</dt>
                    <dd data-user-detail="joined" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Last active</dt>
                    <dd data-user-detail="last_active" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned tasks</dt>
                    <dd data-user-detail="assigned_tasks" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Owned projects</dt>
                    <dd data-user-detail="owned_projects" class="mt-1 text-sm text-slate-900"></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Profile description</dt>
                    <dd data-user-detail="bio" class="mt-1 whitespace-pre-line break-words text-sm text-slate-700"></dd>
                </div>
            </dl>
        </div>
    </x-modal>
@endsection
