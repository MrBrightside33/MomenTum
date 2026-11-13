@props(['sections' => 3])

@php($sectionCount = max(1, min(6, (int) $sections)))

<div class="space-y-6" aria-busy="true">
    @for ($section = 0; $section < $sectionCount; $section++)
        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-hidden="true">
            <x-sk.line w="12rem" h="1.25rem" />
            <x-sk.line w="18rem" h="0.75rem" />
            @foreach (range(1, 3) as $field)
                <div class="space-y-2">
                    <x-sk.line w="7rem" h="0.75rem" />
                    <x-sk.block h="2.75rem" rounded="0.75rem" />
                </div>
            @endforeach
        </section>
    @endfor
</div>
