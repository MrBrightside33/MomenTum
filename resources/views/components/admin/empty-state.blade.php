@props(['title', 'description', 'icon' => 'triangle-alert'])

<div class="flex flex-col items-center px-6 py-12 text-center">
    <span class="flex size-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <h2 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h2>
    <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @if (isset($action))
        <div class="mt-4">{{ $action }}</div>
    @endif
</div>
