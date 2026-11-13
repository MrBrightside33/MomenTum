@props([
    'id',
    'title',
    'subtitle',
    'maxWidth' => 'max-w-[560px]',
    'gap' => 'gap-5',
])

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-950/40 backdrop-blur-sm" data-modal role="presentation" aria-hidden="true">
    <div class="pointer-events-none flex min-h-full items-start justify-center p-2 sm:items-center sm:p-4">
        <div
            data-modal-panel
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $id }}-title"
            aria-describedby="{{ $id }}-desc"
            tabindex="-1"
            class="pointer-events-auto flex w-full {{ $maxWidth }} flex-col {{ $gap }} rounded-2xl bg-white p-4 shadow-[0_25px_25px_rgba(0,0,0,0.25)] sm:p-6"
        >
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 id="{{ $id }}-title" class="text-xl font-bold text-slate-900">{{ $title }}</h2>
                    <p id="{{ $id }}-desc" class="pt-1 text-sm text-slate-500">{{ $subtitle }}</p>
                </div>
                @isset($headerActions)
                    {{ $headerActions }}
                @else
                    <button
                        type="button"
                        data-modal-close
                        aria-label="Close"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40"
                    >
                        <x-icon name="x" class="size-[19px]" />
                    </button>
                @endisset
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
