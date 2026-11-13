@props(['count' => 4])

@php($cardCount = max(1, min(8, (int) $count)))

<section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-busy="true">
    @for ($i = 0; $i < $cardCount; $i++)
        <article class="rounded-2xl border border-slate-200 bg-white p-5" aria-hidden="true">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 space-y-3">
                    <x-sk.line w="8rem" h="0.75rem" />
                    <x-sk.line w="5rem" h="2rem" />
                </div>
                <x-sk.block w="2.75rem" h="2.75rem" rounded="0.75rem" />
            </div>
            <x-sk.line w="75%" h="0.625rem" class="mt-4" />
        </article>
    @endfor
</section>
