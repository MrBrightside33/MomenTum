<aside
    id="admin-sidebar"
    data-admin-sidebar
    class="fixed inset-y-0 left-0 z-30 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
    aria-label="Administration navigation"
    aria-hidden="true"
    inert
>
    <div class="flex h-20 items-center gap-3 px-6">
        <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-white">
            <x-icon name="check" class="size-[22px]" />
        </span>
        <span class="text-xl font-bold tracking-tight text-slate-900">MomenTum</span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5">
        <p class="truncate px-3 pb-3 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Administration</p>
        <div class="space-y-1">
            @php
                $adminLinks = [
                    ['label' => 'Admin overview', 'icon' => 'layout-grid', 'route' => 'admin.overview', 'active' => request()->routeIs('admin.overview')],
                    ['label' => 'Users & roles', 'icon' => 'shield-check', 'route' => 'admin.users.index', 'active' => request()->routeIs('admin.users.*'), 'count' => $adminMemberCount],
                    ['label' => 'All tasks', 'icon' => 'list', 'route' => 'admin.tasks.index', 'active' => request()->routeIs('admin.tasks.*')],
                    ['label' => 'Projects', 'icon' => 'folder', 'route' => 'admin.projects.index', 'active' => request()->routeIs('admin.projects.*')],
                    ['label' => 'Reports', 'icon' => 'chart-line', 'route' => 'admin.reports.index', 'active' => request()->routeIs('admin.reports.*')],
                    ['label' => 'Audit log', 'icon' => 'clock', 'route' => 'admin.audit.index', 'active' => request()->routeIs('admin.audit.*')],
                ];
            @endphp

            @foreach ($adminLinks as $link)
                <a
                    href="{{ route($link['route']) }}"
                    @if ($link['active']) aria-current="page" @endif
                    class="flex h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-base transition {{ $link['active'] ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}"
                >
                    <x-icon :name="$link['icon']" class="size-[19px]" />
                    <span>{{ $link['label'] }}</span>
                    @if (isset($link['count']))
                        <span class="ml-auto rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] text-white">{{ $link['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <p class="px-3 pb-3 pt-8 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Account</p>
        <a
            href="{{ route('admin.settings.index') }}"
            @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif
            class="flex h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-base transition {{ request()->routeIs('admin.settings.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}"
        >
            <x-icon name="settings" class="size-[19px]" />
            <span>Settings</span>
        </a>
    </nav>

    <div class="p-3">
        <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-sm font-bold text-white">
                {{ \App\Support\TaskPresenter::initials(auth()->user()->name) }}
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="truncate text-xs text-slate-500">Workspace admin</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Sign out">
                    <x-icon name="log-out" class="size-[18px]" />
                </button>
            </form>
        </div>
    </div>
</aside>
