@props(['count' => 5])

@php($rowCount = max(1, min(10, (int) $count)))

<div aria-busy="true">
    @for ($i = 0; $i < $rowCount; $i++)
        <x-sk.task-row />
    @endfor
</div>
