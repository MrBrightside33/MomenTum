@props([
    'light' => false,
])

<div {{ $attributes->class('flex items-center gap-3') }}>
    <span class="flex size-12 shrink-0 items-center justify-center rounded-[14px] bg-[#5038f5] text-white">
        <x-icon name="check" class="size-6" />
    </span>
    <span class="text-2xl font-bold tracking-tight {{ $light ? 'text-white' : 'text-slate-900' }}">MomenTum</span>
</div>
