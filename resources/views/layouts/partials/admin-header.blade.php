<header class="sticky top-0 z-10 flex h-20 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur-xl sm:px-8">
    <button
        type="button"
        data-admin-menu-toggle
        aria-controls="admin-sidebar"
        aria-expanded="false"
        aria-label="Open navigation menu"
        class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 lg:hidden"
    >
        <x-icon name="menu" class="size-5" />
    </button>

    <form method="GET" action="{{ route('admin.search') }}" class="relative w-full max-w-md">
        <label for="admin-search" class="sr-only">Search users, tasks, or projects</label>
        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
        <input
            id="admin-search"
            name="q"
            type="search"
            value="{{ request('q') }}"
            placeholder="Search users, tasks, or projects..."
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm text-slate-900 placeholder:text-slate-900/50 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >
    </form>

    <div class="ml-auto">
        <a
            href="{{ route('admin.audit.index', ['group' => 'security']) }}"
            aria-label="Security events"
            title="Security events"
            class="relative flex size-11 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
        >
            <x-icon name="bell" class="size-5" />
            @if ($failedSignInCount > 0)
                <span class="absolute right-2 top-2 size-2 rounded-full bg-red-500 ring-2 ring-white" aria-label="Failed sign-ins in the last 24 hours"></span>
            @endif
        </a>
    </div>
</header>
