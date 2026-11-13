<div class="grid gap-5 xl:grid-cols-2" aria-busy="true">
    @foreach (range(1, 4) as $chart)
        <section class="rounded-2xl border border-slate-200 bg-white p-5" aria-hidden="true">
            <x-sk.line w="8rem" h="1rem" />
            <x-sk.line w="13rem" h="0.75rem" class="mt-2" />
            <div class="mt-6 space-y-4">
                @foreach (range(1, 3) as $bar)
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <x-sk.line w="5rem" h="0.75rem" />
                            <x-sk.line w="3rem" h="0.75rem" />
                        </div>
                        <x-sk.block h="0.5rem" rounded="9999px" />
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
