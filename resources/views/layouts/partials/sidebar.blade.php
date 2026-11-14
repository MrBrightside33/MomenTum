@php
    $workspace = [
        ['label' => 'Overview', 'icon' => 'layout-grid', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'skeleton' => 'dashboard'],
        ['label' => 'Inbox',    'icon' => 'inbox',       'href' => route('inbox'), 'active' => request()->routeIs('inbox'), 'skeleton' => 'inbox'],
        ['label' => 'Calendar', 'icon' => 'calendar',    'href' => route('calendar'), 'active' => request()->routeIs('calendar'), 'skeleton' => 'calendar'],
        ['label' => 'Projects', 'icon' => 'folder',      'href' => route('projects.index'), 'active' => request()->routeIs('projects.*'), 'skeleton' => 'projects'],
        ['label' => 'Team',     'icon' => 'users',       'href' => route('team'), 'active' => request()->routeIs('team'), 'skeleton' => 'team'],
    ];
    $currentUser = auth()->user();
@endphp

<aside class="fixed inset-y-0 left-0 z-20 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
    {{-- Logo --}}
    <div class="flex h-20 items-center px-6">
        <x-brand-logo />
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 py-5">
        <p class="truncate px-3 pb-3 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400" title="{{ $currentUser?->settings['workspace_name'] ?? 'Workspace' }}">
            {{ $currentUser?->settings['workspace_name'] ?? 'Workspace' }}
        </p>

        <div class="space-y-1" data-soft-swap="app-sidebar-nav">
            @foreach ($workspace as $item)
                <a href="{{ $item['href'] }}" data-skeleton="{{ $item['skeleton'] }}"
                   @if (!empty($item['active'])) aria-current="page" @endif
                   class="flex h-11 items-center gap-3 rounded-xl px-3 transition
                          {{ !empty($item['active']) ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                    <x-icon :name="$item['icon']" class="size-[19px]" />
                    <span>{{ $item['label'] }}</span>
                    @if ($item['label'] === 'Inbox')
                        <span data-inbox-sidebar-count aria-label="{{ $unreadInboxCount }} unread notifications" class="ml-auto rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] text-white {{ $unreadInboxCount === 0 ? 'hidden' : '' }}">{{ $unreadInboxCount }}</span>
                    @endisset
                </a>
            @endforeach
        </div>

        <p class="px-3 pb-3 pt-8 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Account</p>
        <a href="{{ route('settings') }}" data-skeleton="settings"
           @if (request()->routeIs('settings*')) aria-current="page" @endif
           class="flex h-11 items-center gap-3 rounded-xl px-3 transition
                  {{ request()->routeIs('settings*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
            <x-icon name="settings" class="size-[19px]" />
            <span>Settings</span>
        </a>
    </nav>

    {{-- User card --}}
    <div class="p-3">
        @if ($currentUser)
            <div class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3">
                @if ($currentUser->avatar_path)
                    <img src="{{ asset('storage/'.$currentUser->avatar_path) }}" alt="" class="size-10 shrink-0 rounded-xl object-cover">
                @else
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-sm font-bold text-white">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($currentUser->name, 0, 2)) }}</div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $currentUser->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $currentUser->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label="Sign out">
                        <x-icon name="log-out" class="size-[18px]" />
                    </button>
                </form>
            </div>
        @else
            <a href="{{ route('login') }}" class="flex h-11 items-center gap-3 rounded-xl px-3 text-slate-600 transition hover:bg-slate-50">
                <x-icon name="log-in" class="size-[19px]" />
                <span>Sign in</span>
            </a>
        @endif
    </div>
</aside>
