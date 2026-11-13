<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-busy="true">
    <div class="grid grid-cols-7 border-b border-slate-100">
        @foreach (range(1, 7) as $day)
            <div class="p-3" aria-hidden="true"><x-sk.line w="2rem" h="0.75rem" /></div>
        @endforeach
    </div>
    <div class="grid grid-cols-7">
        @foreach (range(1, 35) as $cell)
            <div class="min-h-24 border-b border-r border-slate-100 p-2" data-skeleton-row aria-hidden="true">
                <x-sk.line w="1.5rem" h="0.75rem" />
                @if ($cell % 3 === 0)
                    <x-sk.block h="1.25rem" rounded="0.375rem" class="mt-3" />
                @endif
            </div>
        @endforeach
    </div>
</div>
