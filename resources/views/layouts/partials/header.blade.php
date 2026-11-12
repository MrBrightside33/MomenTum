<header class="sticky top-0 z-10 flex h-20 items-center gap-4 border-b border-slate-200 bg-white/90 px-8 backdrop-blur-xl">
    @php
        $savedAppearance = auth()->user()?->settings['appearance'] ?? 'system';
        $isDarkModeEnabled = $savedAppearance === 'dark';
    @endphp

    {{-- Search --}}
    <form
        method="GET"
        action="{{ route('dashboard') }}"
        role="search"
        class="relative w-full max-w-md"
        data-task-search-form
        data-task-search-suggestions-url="{{ route('tasks.search-suggestions') }}"
    >
        <label for="header-task-search" class="sr-only">Search tasks</label>
        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
        <input
            id="header-task-search"
            type="search"
            name="q"
            value="{{ is_string(request('q')) ? request('q') : '' }}"
            placeholder="Search tasks"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="task-search-suggestions"
            aria-describedby="task-search-status"
            autocomplete="off"
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 placeholder:text-slate-900/50 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
        >
        <ul
            id="task-search-suggestions"
            role="listbox"
            aria-label="Task suggestions"
            hidden
            class="absolute left-0 right-0 top-full z-50 mt-2 max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
        ></ul>
        <span id="task-search-status" class="sr-only" role="status" aria-live="polite"></span>
    </form>

    {{-- Actions --}}
    <div class="ml-auto flex items-center gap-2">
        <a href="{{ route('inbox') }}" class="relative rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50" aria-label="Notifications">
            <x-icon name="bell" class="size-5" />
            <span data-header-inbox-count aria-label="{{ $unreadInboxCount }} unread notifications" class="absolute -right-1 -top-1 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[9px] font-bold text-white ring-2 ring-white {{ $unreadInboxCount === 0 ? 'hidden' : '' }}">{{ $unreadInboxCount }}</span>
        </a>

        <form
            method="POST"
            action="{{ route('settings.appearance.update') }}"
            class="inline-flex"
            data-theme-toggle-form
            data-theme-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
        >
            @csrf
            @method('PUT')
            <input type="hidden" name="appearance" value="{{ $isDarkModeEnabled ? 'light' : 'dark' }}" data-theme-appearance-input>
            <button
                type="submit"
                data-theme-toggle
                class="rounded-xl border border-slate-200 p-2.5 text-slate-600 transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-wait disabled:opacity-60"
                aria-label="Dark mode"
                aria-pressed="{{ $isDarkModeEnabled ? 'true' : 'false' }}"
                title="Toggle dark mode"
            >
                <x-icon name="moon" class="size-5 {{ $isDarkModeEnabled ? 'hidden' : '' }}" data-theme-icon="moon" />
                <x-icon name="sun" class="size-5 {{ $isDarkModeEnabled ? '' : 'hidden' }}" data-theme-icon="sun" />
            </button>
        </form>
        <span data-theme-toggle-status role="status" aria-live="polite" aria-atomic="true" class="sr-only"></span>

        @if ($canCreateTasks)
            <button type="button" data-modal-open="create-task-modal" class="flex h-11 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                <x-icon name="plus" class="size-[18px]" />
                <span>New task</span>
            </button>
        @endif
    </div>
</header>
