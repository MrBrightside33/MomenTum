<x-modal id="delete-task-modal" title="Move task to trash?" subtitle="You can restore it before the retention period ends." maxWidth="max-w-[560px]">
    {{-- Delete task confirmation --}}
    <form
        id="delete-task-form"
        action="{{ route('tasks.destroy', ['task' => '__task_id__']) }}"
        method="POST"
        data-delete-url-template="{{ route('tasks.destroy', ['task' => '__task_id__']) }}"
        data-retention-days="{{ config('app.task_retention_days', 30) }}"
        class="flex flex-col gap-5"
    >
        @csrf
        <input type="hidden" name="_method" value="DELETE">

        {{-- Trash retention warning --}}
        <div class="flex items-start gap-3 rounded-xl bg-red-50 p-3.5 text-red-700">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-red-100">
                <x-icon name="trash" class="size-4" />
            </span>
            <p id="delete-task-warning" class="pt-0.5 text-sm leading-5">
                This task will move to trash and can be restored before it is permanently deleted.
            </p>
        </div>

        {{-- Selected task preview --}}
        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p id="delete-task-title" class="text-sm font-semibold text-slate-900"></p>
            <div class="flex flex-wrap items-center gap-2">
                <span id="delete-task-priority" class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600"></span>
                <span id="delete-task-status" class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-600"></span>
            </div>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                <span id="delete-task-project"></span>
                <span aria-hidden="true">•</span>
                <span id="delete-task-due"></span>
            </div>
            <div id="delete-task-assignees" class="flex flex-wrap items-center gap-x-3 gap-y-2"></div>
        </div>

        {{-- Confirmation prompt and server error --}}
        <p class="text-sm text-slate-700">Are you sure you want to delete this task?</p>
        <p id="delete-task-error" class="hidden text-sm font-medium text-red-600" role="alert"></p>

        {{-- Confirmation actions --}}
        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
            <button type="button" data-modal-close data-autofocus class="h-10 rounded-xl border border-slate-200 px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/40">
                Cancel
            </button>
            <button type="submit" id="delete-task-submit" class="flex h-10 items-center gap-2 rounded-xl bg-red-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                <x-icon name="trash" class="size-4" />
                <span id="delete-task-submit-label">Delete task</span>
            </button>
        </div>
    </form>
</x-modal>
