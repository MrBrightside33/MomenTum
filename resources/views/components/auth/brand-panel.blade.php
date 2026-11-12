<aside class="relative hidden min-h-screen overflow-hidden bg-[#432dd7] px-16 pt-12 lg:block">
    {{-- Brand panel decorations --}}
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -right-16 size-80 rounded-full border-[48px] border-violet-400/20"></div>
    <div aria-hidden="true" class="pointer-events-none absolute right-28 top-20 size-20 rotate-12 rounded-3xl border border-white/10 bg-white/5"></div>

    {{-- Brand message --}}
    <div class="relative max-w-[569px]">
        <div class="flex items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20">
                <x-icon name="square-check" class="size-[22px]" />
            </span>
            <span class="text-xl font-bold tracking-[-0.5px] text-white">MomenTum</span>
        </div>

        <div class="mb-2 mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-100">
            <x-icon name="sparkles" class="size-[15px] text-indigo-100" />
            <span>Your personal productivity space</span>
        </div>

        <h1 class="max-w-lg text-5xl font-bold leading-[1.08] tracking-[-0.04em] text-white">
            A clearer path to getting moments done.
        </h1>

        <p class="mt-6 max-w-lg text-lg leading-8 text-indigo-100">
            Organize your tasks, manage deadlines, and keep your goals moving forward.
        </p>

        {{-- Progress glass card --}}
        <div class="mt-12 flex items-center gap-4 rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur-sm">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-violet-400/30 text-white">
                <x-icon name="check" class="size-6" />
            </span>
            <div>
                <p class="text-base font-semibold text-white">Make progress visible</p>
                <p class="mt-1 text-sm text-indigo-100">One focused task at a time.</p>
            </div>
        </div>

        <p class="mt-2 text-sm text-indigo-200">MomenTum · Stay organized, stay productive.</p>
    </div>
</aside>
