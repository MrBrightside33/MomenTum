@props(['columns' => 5, 'rows' => 5, 'avatars' => false])

@php
    $columnCount = max(2, min(8, (int) $columns));
    $rowCount = max(1, min(10, (int) $rows));
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-busy="true">
    <div class="grid gap-4 border-b border-slate-100 bg-slate-50 p-4" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));" aria-hidden="true">
        @for ($column = 0; $column < $columnCount; $column++)
            <x-sk.line w="70%" h="0.75rem" />
        @endfor
    </div>
    @for ($row = 0; $row < $rowCount; $row++)
        <div class="grid items-center gap-4 border-b border-slate-100 p-4 last:border-b-0" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));" data-skeleton-row aria-hidden="true">
            @for ($column = 0; $column < $columnCount; $column++)
                @if ($avatars && $column === 0)
                    <x-sk.avatar-line />
                @else
                    <x-sk.line w="{{ $column === 0 ? '85%' : '65%' }}" h="0.75rem" />
                @endif
            @endfor
        </div>
    @endfor
</div>
