@props(['status'])

@php
    $states = [
        'active' => ['label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'pending' => ['label' => 'Invited', 'class' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'inactive' => ['label' => 'Inactive', 'class' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
        'open' => ['label' => 'Open', 'class' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
        'in_progress' => ['label' => 'In progress', 'class' => 'bg-indigo-50 text-indigo-700', 'dot' => 'bg-indigo-500'],
        'completed' => ['label' => 'Completed', 'class' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'archived' => ['label' => 'Archived', 'class' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'],
    ];
    $state = $states[$status] ?? ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'bg-slate-100 text-slate-600', 'dot' => 'bg-slate-400'];
@endphp

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $state['class'] }}">
    <span class="size-1.5 rounded-full {{ $state['dot'] }}"></span>
    {{ $state['label'] }}
</span>
