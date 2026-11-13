@props(['count' => 6])

@php($cardCount = max(1, min(10, (int) $count)))

<div class="grid items-stretch gap-4 p-4 sm:p-5 md:grid-cols-2 xl:grid-cols-3" aria-busy="true">
    @for ($i = 0; $i < $cardCount; $i++)
        <x-sk.project-card />
    @endfor
</div>
