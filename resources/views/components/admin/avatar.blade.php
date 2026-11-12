@props(['user', 'size' => 'md'])

@php
    $sizes = ['sm' => 'size-8', 'md' => 'size-10', 'lg' => 'size-12'];
    $colors = [
        'bg-indigo-100 text-indigo-700',
        'bg-violet-100 text-violet-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700',
        'bg-sky-100 text-sky-700',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $colorClass = $colors[$user->getKey() % count($colors)];
    $initials = \App\Support\TaskPresenter::initials($user->name);
@endphp

@if ($user->avatar_path)
    <img src="{{ asset('storage/'.$user->avatar_path) }}" alt="" class="{{ $sizeClass }} shrink-0 rounded-xl object-cover">
@else
    <span class="flex {{ $sizeClass }} shrink-0 items-center justify-center rounded-xl text-xs font-bold {{ $colorClass }}" aria-hidden="true">
        {{ $initials }}
    </span>
@endif
