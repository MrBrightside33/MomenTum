import { updateDashboardTaskStats } from './task-stats';
import { showToast } from './toast';
import { request } from './http';
import { setButtonLoading } from './loading';
import { UNDO_SECONDS } from './loading-config';

const initializeTaskDeletion = () => {
    const modal = document.getElementById('delete-task-modal');
    const form = document.getElementById('delete-task-form');

    if (!modal || !form) {
        return;
    }

    const title = document.getElementById('delete-task-title');
    const priority = document.getElementById('delete-task-priority');
    const status = document.getElementById('delete-task-status');
    const project = document.getElementById('delete-task-project');
    const due = document.getElementById('delete-task-due');
    const assignees = document.getElementById('delete-task-assignees');
    const warning = document.getElementById('delete-task-warning');
    const error = document.getElementById('delete-task-error');
    const submitButton = document.getElementById('delete-task-submit');
    let taskRow = null;
    let isSubmitting = false;
    let allowClose = false;
    let restoreSubmitButton = null;

    const showError = (message) => {
        error.textContent = message;
        error.classList.remove('hidden');
    };

    const removeDeletedTask = (deletedStatus) => {
        const deletedRow = taskRow;
        allowClose = true;
        const modalClosed = window.Modal.close(modal.id);
        allowClose = false;

        if (!modalClosed) {
            console.error('The delete confirmation could not close after a successful response.');
            showError("Couldn't delete the task. Please try again.");
            setSubmitting(false);

            return false;
        }

        deletedRow.remove();
        document.getElementById('task-list')?.dispatchEvent(new CustomEvent('dashboard:tasks-changed'));
        updateDashboardTaskStats(deletedStatus, null, -1);
        document.querySelector('header [data-modal-open="create-task-modal"]')?.focus();

        return true;
    };

    const setSubmitting = (submitting) => {
        isSubmitting = submitting;
        submitButton.disabled = submitting;

        if (submitting) {
            restoreSubmitButton = setButtonLoading(submitButton, { label: 'Moving task to trash…' });
        } else if (restoreSubmitButton) {
            restoreSubmitButton();
            restoreSubmitButton = null;
        }
    };

    const updatePreview = (row) => {
        const taskTitle = row.dataset.taskTitle ?? '';
        const taskPriority = row.dataset.taskPriority ?? '';
        const taskStatus = row.dataset.taskStatus ?? '';
        const taskProject = row.dataset.taskProject ?? '';

        title.textContent = taskTitle;
        priority.textContent = `${taskPriority} priority`;
        status.textContent = taskStatus.toLowerCase();
        project.textContent = taskProject;
        due.textContent = row.querySelector('[data-task-due-label]')?.textContent ?? '';
        warning.textContent = `This task will move to trash and be permanently deleted after ${form.dataset.retentionDays} days.`;

        priority.className = 'rounded-full px-2.5 py-1 text-[11px] font-semibold';
        priority.classList.add(
            taskPriority === 'High' ? 'bg-red-50' : taskPriority === 'Medium' ? 'bg-amber-50' : 'bg-emerald-50',
            taskPriority === 'High' ? 'text-red-600' : taskPriority === 'Medium' ? 'text-amber-600' : 'text-emerald-600',
        );
        status.className = 'rounded-full px-2.5 py-1 text-[11px] font-semibold';
        status.classList.add(
            taskStatus === 'Completed' ? 'bg-emerald-50' : 'bg-indigo-50',
            taskStatus === 'Completed' ? 'text-emerald-600' : 'text-indigo-600',
        );

        assignees.replaceChildren();
        const taskAssignees = Array.from(row.querySelectorAll('[data-task-assignee]'));

        if (taskAssignees.length === 0) {
            const emptyMessage = document.createElement('span');
            emptyMessage.className = 'text-xs text-slate-400';
            emptyMessage.textContent = 'No assignees';
            assignees.append(emptyMessage);
        }

        taskAssignees.forEach((assignee) => {
            const avatar = document.createElement('span');
            const name = document.createElement('span');
            const member = document.createElement('span');

            avatar.className = 'flex size-6 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[9px] font-bold text-slate-600';
            avatar.textContent = assignee.textContent;
            name.className = 'text-xs text-slate-600';
            name.textContent = assignee.dataset.assigneeName;
            member.className = 'flex items-center gap-1.5';
            member.append(avatar, name);
            assignees.append(member);
        });
    };

    const resetModal = () => {
        taskRow = null;
        error.textContent = '';
        error.classList.add('hidden');
        setSubmitting(false);
    };

    modal.addEventListener('modal:beforeclose', (event) => {
        if (isSubmitting && !allowClose) {
            event.preventDefault();
        }
    });
    modal.addEventListener('modal:close', resetModal);

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const deleteButton = event.target.closest('[data-delete-task]');

        if (!deleteButton) {
            return;
        }

        event.preventDefault();
        const row = deleteButton.closest('[data-task-row]');

        if (!row?.dataset.taskId) {
            console.error('Cannot delete a task without a task id.');
            return;
        }

        taskRow = row;
        form.action = form.dataset.deleteUrlTemplate.replace(
            '__task_id__',
            encodeURIComponent(row.dataset.taskId),
        );
        updatePreview(row);
        error.textContent = '';
        error.classList.add('hidden');
        window.Modal.open(modal.id);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting || !taskRow?.isConnected) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            console.error('The CSRF token is missing from the task deletion form.');
            showError("Couldn't delete the task. Please refresh and try again.");

            return;
        }

        setSubmitting(true);

        try {
            const { data: payload } = await request(form.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: new FormData(form),
            });

            if (String(payload.task_id) !== taskRow.dataset.taskId) {
                throw new Error('The task deletion response did not match the selected task.');
            }

            const deletedTask = taskRow;
            const deletedStatus = deletedTask.dataset.taskStatus;
            const restoreUrl = typeof payload.restore_url === 'string'
                ? new URL(payload.restore_url, window.location.origin)
                : null;
            const removed = removeDeletedTask(deletedStatus);

            if (removed) {
                if (!restoreUrl || restoreUrl.origin !== window.location.origin) {
                    console.error('The task deletion response did not contain a valid restore URL.');
                    showToast('info', 'Task moved to trash');

                    return;
                }

                showToast({
                    type: 'success',
                    title: 'Task moved to trash',
                    message: 'You can undo this deletion.',
                    actionLabel: 'Undo',
                    duration: UNDO_SECONDS * 1000,
                    onAction: async () => {
                        const { data: restored } = await request(restoreUrl.href, { method: 'POST' });
                        const markup = document.createElement('template');
                        markup.innerHTML = typeof restored.html === 'string' ? restored.html : '';
                        const restoredRow = markup.content.firstElementChild;

                        if (
                            String(restored.task_id) !== deletedTask.dataset.taskId
                            || !restored.task
                            || typeof restored.task.title !== 'string'
                            || typeof restored.task.status !== 'string'
                            || !restoredRow?.matches('[data-task-row]')
                            || markup.content.childElementCount !== 1
                        ) {
                            throw new Error('The task restore response was invalid.');
                        }

                        const taskList = document.getElementById('task-list');

                        if (taskList) {
                            taskList.prepend(restoredRow);
                            taskList.dispatchEvent(new CustomEvent('dashboard:tasks-changed'));
                            updateDashboardTaskStats(null, restored.task.status, 1);
                        }

                        showToast('success', 'Task restored', restored.task.title);
                    },
                });
            }
        } catch (error) {
            if (error.status === 404) {
                if (removeDeletedTask(taskRow.dataset.taskStatus)) {
                    showToast('info', 'Task already deleted', 'Removed it from your list.');
                }

                return;
            }

            console.error('Task deletion request failed.', error);
            showError(error.status === 403
                ? "You don't have permission to delete this task."
                : "Couldn't delete the task. Please try again.");
            setSubmitting(false);
        }
    });
};

initializeTaskDeletion();
