<section class="py-32">
    <div class="mx-auto max-w-5xl px-8">
        <div class="relative overflow-hidden rounded-[32px] bg-gradient-to-br from-indigo-600 to-violet-600 p-8 text-center shadow-[0_25px_50px_-12px_rgba(79,57,246,0.2)] md:p-16">
            <div aria-hidden="true" class="absolute -left-16 -top-24 h-64 w-64 rounded-full border-[48px] border-white/5"></div>
            <div aria-hidden="true" class="absolute -bottom-24 -right-12 h-72 w-72 rounded-full border-[48px] border-white/5"></div>

            <div class="relative z-10 flex flex-col items-center">
                <div class="flex size-[52px] items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                    <x-icon name="check" class="size-[25px] text-white" />
                </div>

                <h2 class="mt-6 max-w-2xl text-4xl font-extrabold tracking-[-0.035em] text-white leading-[1.0] md:text-5xl">
                    Make today the day work finally flows.
                </h2>

                <p class="mt-5 max-w-xl text-base leading-7 text-indigo-100">
                    Join thousands of people turning their biggest plans into meaningful progress.
                </p>

                <a href="{{ route('login') }}" class="mt-8 inline-flex h-[52px] items-center gap-2 rounded-xl bg-white px-7 text-base font-semibold text-indigo-700 shadow-xl shadow-black/10 transition hover:bg-indigo-50">
                    <span>Get started</span>
                    <x-icon name="arrow-right" class="size-[18px]" />
                </a>
            </div>
        </div>
    </div>
</section>
