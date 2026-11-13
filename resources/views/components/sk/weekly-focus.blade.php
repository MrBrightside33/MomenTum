<section class="rounded-2xl border border-slate-200 bg-white p-5" aria-busy="true">
    <div class="flex items-center justify-between">
        <div class="space-y-2">
            <x-sk.line w="8rem" h="1rem" />
            <x-sk.line w="6rem" h="0.75rem" />
        </div>
        <x-sk.circle size="2rem" />
    </div>
    <div class="mt-6 flex h-40 items-end gap-2" aria-hidden="true">
        @foreach (range(1, 7) as $day)
            <div class="flex flex-1 flex-col items-center gap-2">
                <x-sk.block w="100%" h="{{ ($day % 3 + 1) * 1.5 }}rem" rounded="0.375rem" />
                <x-sk.line w="1.5rem" h="0.625rem" />
            </div>
        @endforeach
    </div>
    <x-sk.block h="2.75rem" rounded="0.75rem" class="mt-5" />
</section>
