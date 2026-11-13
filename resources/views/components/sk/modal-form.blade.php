@props(['fields' => 6])

@php($fieldCount = max(2, min(10, (int) $fields)))

<div class="space-y-5" aria-busy="true" aria-hidden="true">
    @for ($field = 0; $field < $fieldCount; $field++)
        <div class="space-y-2">
            <x-sk.line w="7rem" h="0.75rem" />
            <x-sk.block h="2.75rem" rounded="0.75rem" />
        </div>
    @endfor
    <div class="flex justify-end gap-3 pt-2">
        <x-sk.block w="5rem" h="2.75rem" rounded="0.75rem" />
        <x-sk.block w="8rem" h="2.75rem" rounded="0.75rem" />
    </div>
</div>
