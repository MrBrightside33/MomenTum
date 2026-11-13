<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-8" aria-busy="true" aria-hidden="true">
    <div class="min-w-0 space-y-6">
        <section class="space-y-4">
            <div class="flex items-start gap-3">
                <x-sk.block w="2.75rem" h="2.75rem" rounded="0.75rem" />
                <div class="flex-1 space-y-3">
                    <x-sk.line w="70%" h="1.75rem" />
                    <x-sk.line w="8rem" h="1.5rem" />
                </div>
            </div>
            <x-sk.line w="6rem" h="0.875rem" />
            <x-sk.block h="3rem" rounded="0.75rem" />
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex justify-between">
                <x-sk.line w="8rem" h="1rem" />
                <x-sk.line w="3rem" h="1.5rem" />
            </div>
            <div class="mt-6 flex justify-between">
                @foreach (range(1, 5) as $stage)
                    <x-sk.circle size="2.75rem" />
                @endforeach
            </div>
            <x-sk.block h="2.75rem" rounded="0.75rem" class="mt-5" />
        </section>
        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
            <x-sk.line w="7rem" h="1rem" />
            @foreach (range(1, 3) as $activity)
                <x-sk.avatar-line />
            @endforeach
        </section>
    </div>
    <aside class="space-y-4">
        <x-sk.block h="16rem" rounded="1rem" />
        <x-sk.block h="12rem" rounded="1rem" />
    </aside>
</div>
