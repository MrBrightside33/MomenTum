@props(['count' => 5])

@php($itemCount = max(1, min(10, (int) $count)))

<div class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white" aria-busy="true">
    @for ($item = 0; $item < $itemCount; $item++)
        <div class="flex items-start gap-4 p-5" data-skeleton-row aria-hidden="true">
            <x-sk.circle size="2.5rem" />
            <div class="min-w-0 flex-1 space-y-2">
                <x-sk.line w="min(20rem, 75%)" h="0.875rem" />
                <x-sk.line w="min(32rem, 95%)" h="0.75rem" />
                <x-sk.line w="7rem" h="0.625rem" />
            </div>
            <x-sk.circle size="2rem" />
        </div>
    @endfor
</div>
