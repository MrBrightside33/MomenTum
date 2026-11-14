<header class="fixed top-0 z-50 w-full border-b border-slate-200/80 bg-white/85 backdrop-blur-xl">
    <div class="mx-auto flex h-[72px] max-w-[1280px] items-center justify-between px-8">
        <a href="{{ route('home') }}" aria-label="MomenTum home">
            <x-brand-logo />
        </a>

        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700">
            Login
        </a>
    </div>
</header>
