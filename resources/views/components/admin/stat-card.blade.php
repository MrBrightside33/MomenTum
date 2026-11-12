@props(['label', 'value', 'note', 'icon', 'tone' => 'indigo'])

@php
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
    ];
    $toneClass = $tones[$tone] ?? $tones['indigo'];
@endphp

<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="pt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        </div>
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $toneClass }}">
            <x-icon :name="$icon" class="size-[21px]" />
        </span>
    </div>
    <p class="pt-4 text-xs font-medium text-slate-500">{{ $note }}</p>
</article>
