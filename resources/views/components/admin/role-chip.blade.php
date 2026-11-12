@props(['role'])

@php
    $roleValue = $role instanceof \BackedEnum ? $role->value : (string) $role;
    $permission = config("permissions.{$roleValue}");
    $label = $permission['label'] ?? ucfirst($roleValue);
    $dotClass = $permission['dot'] ?? 'bg-slate-400';
@endphp

<span class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3.5 text-sm text-slate-700">
    <span class="size-2.5 rounded-full {{ $dotClass }}"></span>
    {{ $label }}
</span>
