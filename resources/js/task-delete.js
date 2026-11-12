import { updateDashboardTaskStats } from './task-stats';

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
    const submitLabel = document.getElementById('delete-task-submit-label');
    let taskRow = null;
    let isSubmitting = false;
    let allowClose = false;

    const showError = (message) => {
        error.textContent = message;
        error.classList.remove('hidden');
    };

    const setSubmitting = (submitting) => {
        isSubmitting = submitting;
        submitButton.disabled = submitting;
        submitLabel.textContent = submitting ? 'Deleting…' : 'Delete task';
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
        warning.textContent = `This task and its activity history will be permanently deleted from ${taskProject} and removed for all assignees.`;

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

        let response;

        try {
            response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });
        } catch (requestError) {
            console.error('Task deletion request failed.', requestError);
            showError("Couldn't delete the task. Please try again.");
            setSubmitting(false);

            return;
        }

        let payload;

        try {
            payload = await response.json();
        } catch (responseError) {
            console.error('Could not read the task deletion response.', responseError);
            showError("Couldn't delete the task. Please try again.");
            setSubmitting(false);

            return;
        }

        if (!response.ok || payload.task_id !== taskRow.dataset.taskId) {
            showError(payload.message ?? "Couldn't delete the task. Please try again.");
            setSubmitting(false);

            return;
        }

        const deletedStatus = taskRow.dataset.taskStatus;
        const deletedRow = taskRow;
        allowClose = true;
        const modalClosed = window.Modal.close(modal.id);
        allowClose = false;

        if (!modalClosed) {
            console.error('The delete confirmation could not close after a successful response.');
            showError("Couldn't delete the task. Please try again.");
            setSubmitting(false);

            return;
        }

        deletedRow.remove();
        updateDashboardTaskStats(deletedStatus, null, -1);
        document.querySelector('header [data-modal-open="create-task-modal"]')?.focus();
    });
};

initializeTaskDeletion();
