<div class="space-y-5" aria-busy="true" aria-hidden="true">
    <div class="flex items-center gap-4">
        <x-sk.circle size="3.5rem" />
        <div class="flex-1 space-y-2">
            <x-sk.line w="12rem" h="1.25rem" />
            <x-sk.line w="16rem" h="0.75rem" />
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (range(1, 8) as $field)
            <div class="space-y-2">
                <x-sk.line w="5rem" h="0.625rem" />
                <x-sk.line w="75%" h="0.875rem" />
            </div>
        @endforeach
    </div>
</div>
