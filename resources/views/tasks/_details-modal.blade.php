<x-modal id="task-details-modal" title="Task details" subtitle="Review the task and its current progress.">
    <div data-task-details class="space-y-5">
        <div>
            <h3 data-td-title class="text-xl font-bold text-slate-900"></h3>
            <p data-td-description class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-slate-600"></p>
        </div>
        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4" aria-label="Task progress">
            <div class="flex items-center justify-between gap-3">
                <span class="text-sm font-semibold text-slate-700">Progress</span>
                <span data-td-progress-label class="text-xs font-medium text-slate-500"></span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Task progress" aria-valuemin="0" aria-valuemax="100">
                <div data-td-progress-bar class="h-full rounded-full bg-indigo-500 transition-[width] duration-300" style="width: 0%"></div>
            </div>
            <p data-td-progress class="mt-2 text-right text-xs font-semibold tabular-nums text-slate-700"></p>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <a data-td-project-link href="#" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-indigo-600 transition hover:bg-indigo-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                <x-icon name="folder" class="size-4" />
                Open in Projects
            </a>
            <button type="button" data-modal-close class="min-h-11 rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Close</button>
        </div>
    </div>
</x-modal>
