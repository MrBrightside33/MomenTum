{{-- Task creation success feedback --}}
<div id="task-success-modal" class="fixed inset-0 z-[60] hidden overflow-y-auto bg-slate-950/30 backdrop-blur-sm" data-modal role="presentation" aria-hidden="true">
    <div class="pointer-events-none flex min-h-full items-center justify-center p-4">
        <div
            data-modal-panel
            role="dialog"
            aria-modal="true"
            aria-labelledby="task-success-title"
            aria-describedby="task-success-description"
            tabindex="-1"
            class="pointer-events-auto flex w-full max-w-[216px] flex-col items-center rounded-xl bg-white px-5 pb-3 pt-4 shadow-xl"
        >
            {{-- Success icon and message --}}
            <span class="flex size-12 items-center justify-center rounded-full border-2 border-emerald-500 text-emerald-600" aria-hidden="true">
                <x-icon name="check" class="size-6" />
            </span>
            <h2 id="task-success-title" class="mt-2 text-center text-xs font-semibold text-slate-900">Add Task Successfully</h2>
            <p id="task-success-description" class="sr-only">Your task has been added to the task list.</p>

            {{-- View the newly created task --}}
            <div class="mt-3 flex w-full justify-center border-t border-slate-100 pt-2">
                <button
                    type="button"
                    data-success-view-task
                    class="h-7 rounded-lg bg-indigo-600 px-3 text-[10px] font-medium text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    View Task
                </button>
            </div>
        </div>
    </div>
</div>
