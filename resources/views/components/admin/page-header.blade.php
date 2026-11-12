@props(['eyebrow' => 'ADMINISTRATION', 'title', 'subtitle'])

<section class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        <p class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-indigo-600">
            <x-icon name="shield-check" class="size-[17px]" />
            {{ $eyebrow }}
        </p>
        <h1 class="pt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ $title }}</h1>
        <p class="pt-2 text-sm text-slate-500 sm:text-base">{{ $subtitle }}</p>
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</section>
