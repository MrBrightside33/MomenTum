@props(['count' => 5])

<div class="space-y-3" aria-busy="true">
    @for ($result = 0; $result < max(1, min(10, (int) $count)); $result++)
        <article class="rounded-xl border border-slate-200 bg-white p-4" data-skeleton-row aria-hidden="true">
            <x-sk.line w="55%" h="1rem" />
            <x-sk.line w="90%" h="0.75rem" class="mt-3" />
        </article>
    @endfor
</div>
