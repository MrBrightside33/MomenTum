@php
    $totalTasks = $taskCounts['total'];
    $totalProjects = $projectCounts['total'];
    $totalUsers = $userCounts['total'];
@endphp

<div
    id="admin-reports-data"
    data-lazy-region="{{ route('admin.reports.data') }}"
    data-lazy-skeleton="admin-reports"
    class="space-y-6"
>
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Workspace totals">
        <x-admin.stat-card label="Accounts" :value="number_format($totalUsers)" note="Across all account states" icon="users" />
        <x-admin.stat-card label="Tasks" :value="number_format($totalTasks)" note="Across the workspace" icon="list-checks" tone="violet" />
        <x-admin.stat-card label="Projects" :value="number_format($totalProjects)" note="Across all project states" icon="folder" tone="emerald" />
        <x-admin.stat-card label="Recent audit events" :value="number_format($recentAuditCount)" note="Recorded in the last 7 days" icon="clock" tone="amber" />
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Account status</h2>
                    <p class="mt-1 text-sm text-slate-500">Workspace members by access state.</p>
                </div>
                <a href="{{ route('admin.reports.export', 'users') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Export</a>
            </div>
            <div class="mt-6 space-y-4">
                @foreach (['active' => 'Active', 'pending' => 'Invited', 'inactive' => 'Inactive'] as $status => $label)
                    @php($percentage = $totalUsers > 0 ? (int) round(($userCounts[$status] / $totalUsers) * 100) : 0)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-700">{{ $label }}</span>
                            <span class="tabular-nums text-slate-500">{{ number_format($userCounts[$status]) }} · {{ $percentage }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Task status</h2>
                    <p class="mt-1 text-sm text-slate-500">Current task completion distribution.</p>
                </div>
                <a href="{{ route('admin.reports.export', 'tasks') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Export</a>
            </div>
            <div class="mt-6 space-y-4">
                @foreach ($taskStatuses as $status)
                    @php($count = $taskCounts['statuses'][$status->value] ?? 0)
                    @php($percentage = $totalTasks > 0 ? (int) round(($count / $totalTasks) * 100) : 0)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-700">{{ $status->value }}</span>
                            <span class="tabular-nums text-slate-500">{{ number_format($count) }} · {{ $percentage }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-violet-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-900">Project status</h2>
                    <p class="mt-1 text-sm text-slate-500">Projects by their current lifecycle state.</p>
                </div>
                <a href="{{ route('admin.reports.export', 'projects') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Export</a>
            </div>
            <div class="mt-6 space-y-4">
                @foreach ($projectStatuses as $status)
                    @php($percentage = $totalProjects > 0 ? (int) round(($projectCounts[$status->value] / $totalProjects) * 100) : 0)
                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-medium text-slate-700">{{ str_replace('_', ' ', ucfirst($status->value)) }}</span>
                            <span class="tabular-nums text-slate-500">{{ number_format($projectCounts[$status->value]) }} · {{ $percentage }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div>
                <span class="flex size-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <x-icon name="shield-check" class="size-5" />
                </span>
                <h2 class="mt-4 font-semibold text-slate-900">Security activity</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    {{ number_format($recentAuditCount) }} audit events were recorded over the last 7 days. Review authentication events and workspace actions in the audit log.
                </p>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('admin.audit.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Review audit log
                </a>
                <a href="{{ route('admin.reports.export', 'audit') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Export audit CSV
                </a>
            </div>
        </section>
    </div>
</div>
