<div class="flex flex-col gap-4 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between" aria-busy="true" aria-hidden="true">
    <x-sk.block w="min(22rem, 100%)" h="2.5rem" rounded="0.75rem" />
    <div class="flex gap-2">
        @foreach (range(1, 4) as $tab)
            <x-sk.block w="5.5rem" h="2.25rem" rounded="0.5rem" />
        @endforeach
    </div>
</div>
