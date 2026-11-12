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
            class="pointer-events-auto flex w-full max-w-[280px] flex-col items-center rounded-2xl border border-[#263449] bg-[#111c2f] px-6 pb-5 pt-5 shadow-[0_24px_60px_rgba(15,23,42,0.45)]"
        >
            {{-- Success icon and message --}}
            <span class="flex size-16 items-center justify-center rounded-full border-[3px] border-emerald-400 bg-emerald-500/10 text-emerald-400 shadow-[0_0_0_4px_rgba(16,185,129,0.08)]" aria-hidden="true">
                <x-icon name="check" class="size-8" />
            </span>
            <h2 id="task-success-title" class="mt-4 text-center text-lg font-semibold text-[#f8fafc]">Add Task Successfully</h2>
            <p id="task-success-description" class="sr-only">Your task has been added to the task list.</p>

            {{-- View the newly created task --}}
            <div class="mt-5 flex w-full justify-center">
                <button
                    type="button"
                    data-success-view-task
                    class="h-11 w-full rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-[#111c2f]"
                >
                    View Task
                </button>
            </div>
        </div>
    </div>
</div>
