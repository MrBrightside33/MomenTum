import { updateDashboardTaskStats } from './task-stats';
import { request } from './http';
import { setButtonLoading } from './loading';

let statusMessage;
const pendingTaskIds = new Set();

const announce = (message, isError = false) => {
    const taskList = document.getElementById('task-list');

    if (!taskList) {
        return;
    }

    if (!statusMessage?.isConnected) {
        statusMessage = document.createElement('p');
        statusMessage.className = 'px-5 py-3 text-sm';
        statusMessage.setAttribute('aria-live', 'polite');
        taskList.before(statusMessage);
    }

    statusMessage.classList.toggle('text-red-600', isError);
    statusMessage.classList.toggle('text-emerald-700', !isError);
    statusMessage.textContent = message;
};

document.addEventListener('click', async (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const button = event.target.closest('[data-task-status-toggle]');
    const row = button?.closest('[data-task-row]');
    const taskList = document.getElementById('task-list');

    if (!(button instanceof HTMLButtonElement) || !row || !taskList?.contains(row)) {
        return;
    }

    const taskId = row.dataset.taskId;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!taskId || !row.dataset.taskStatusUrl || !csrfToken || pendingTaskIds.has(taskId)) {
        announce('Could not update this task. Refresh the page and try again.', true);
        return;
    }

    const previousStatus = row.dataset.taskStatus;
    const nextStatus = previousStatus === 'Completed' ? 'Open' : 'Completed';
    pendingTaskIds.add(taskId);
    const restoreButton = setButtonLoading(button, {
        label: nextStatus === 'Completed' ? 'Completing task…' : 'Reopening task…',
    });
    let buttonState = 'idle';

    try {
        const { data: payload } = await request(row.dataset.taskStatusUrl, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            json: { status: nextStatus },
        });

        const markup = document.createElement('template');
        markup.innerHTML = typeof payload.html === 'string' ? payload.html : '';
        const updatedRow = markup.content.firstElementChild;

        if (!updatedRow?.matches('[data-task-row]') || markup.content.childElementCount !== 1) {
            console.error('The task status response did not contain a valid task row.');
            announce('Could not update this task. Please refresh and try again.', true);
            buttonState = 'error';
            restoreButton('error');

            return;
        }

        row.replaceWith(updatedRow);
        taskList.dispatchEvent(new CustomEvent('dashboard:tasks-changed'));
        updateDashboardTaskStats(previousStatus, payload.status, 0);
        announce(payload.message ?? 'Task status updated.');
    } catch (error) {
        console.error('Task status request failed.', error);
        announce(error.data?.message ?? error.message ?? 'Could not update this task. Please try again.', true);
        buttonState = 'error';
        restoreButton('error');
    } finally {
        pendingTaskIds.delete(taskId);

        if (button.isConnected && buttonState === 'idle') {
            restoreButton('idle');
        }
    }
});
