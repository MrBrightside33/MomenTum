<header class="sticky top-0 z-10 flex h-20 items-center gap-4 border-b border-slate-200 bg-white/90 px-8 backdrop-blur-xl">
    @php
        $savedAppearance = auth()->user()?->settings['appearance'] ?? 'system';
        $isDarkModeEnabled = $savedAppearance === 'dark';
    @endphp

    {{-- Search --}}
    <div class="relative w-full max-w-md">
        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-[18px] -translate-y-1/2 text-slate-400" />
        <input type="text" placeholder="Search tasks, people, or projects"
               class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 placeholder:text-slate-900/50 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
    </div>

    {{-- Actions --}}
    <div class="ml-auto flex items-center gap-2">
        <button type="button" class="relative rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50" aria-label="Notifications">
            <x-icon name="bell" class="size-5" />
            <span class="absolute right-2 top-2 size-2 rounded-full bg-red-500 ring-2 ring-white"></span>
        </button>

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

        <button type="button" data-modal-open="create-task-modal" class="flex h-11 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
            <x-icon name="plus" class="size-[18px]" />
            <span>New task</span>
        </button>
    </div>
</header>
