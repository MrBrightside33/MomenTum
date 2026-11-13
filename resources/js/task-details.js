import { showToast } from './toast';
import { request } from './http';

let activeTaskDetailsController;

const initializeTaskDetails = () => {
    activeTaskDetailsController?.abort();
    activeTaskDetailsController = new AbortController();
    const modal = document.getElementById('task-details-modal');
    const details = modal?.querySelector('[data-task-details]');

    if (!modal || !details) {
        return;
    }

    let activeTaskId = null;
    let requestSequence = 0;

    const setText = (selector, value) => {
        const element = details.querySelector(selector);

        if (element) {
            element.textContent = value ?? '';
        }
    };

    const renderProgress = (progress, label) => {
        const value = Number(progress);
        const progressBar = details.querySelector('[data-td-progress-bar]');
        const progressLabel = details.querySelector('[data-td-progress-label]');
        const progressText = details.querySelector('[data-td-progress]');
        const progressTrack = details.querySelector('[role="progressbar"]');

        if (!Number.isInteger(value) || value < 0 || value > 100) {
            throw new Error('The task response contains invalid progress.');
        }

        progressBar.style.width = `${value}%`;
        progressTrack.setAttribute('aria-valuenow', String(value));
        progressLabel.textContent = label ?? '';
        progressText.textContent = `${value}%`;
        progressBar.classList.toggle('bg-emerald-500', value === 100);
        progressBar.classList.toggle('bg-indigo-500', value !== 100);
    };

    const renderTask = (task) => {
        setText('[data-td-title]', task.title);
        setText('[data-td-description]', task.description || 'No description added.');
        renderProgress(task.progress, task.progress_label);

        const projectLink = details.querySelector('[data-td-project-link]');
        projectLink.href = task.urls.project;
    };

    document.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-task-details-open]');
        const row = trigger?.closest('[data-task-row]');

        if (!row || !modal) {
            return;
        }

        event.preventDefault();
        const taskId = row.dataset.taskId;
        const sequence = ++requestSequence;
        activeTaskId = taskId;

        try {
            const cachedTask = JSON.parse(row.dataset.task ?? '{}');
            renderTask({
                title: row.dataset.taskTitle ?? '',
                description: row.dataset.taskDescription ?? '',
                progress: cachedTask.progress,
                progress_label: cachedTask.progress_label,
                urls: { project: row.dataset.taskProjectUrl },
            });
            window.Modal.open(modal.id);
        } catch (error) {
            console.error('Could not render the cached task details.', error);
            showToast('error', "Couldn't open task details. Please refresh the page.");

            return;
        }

        try {
            const { data: payload } = await request(row.dataset.taskShowUrl, {
                headers: { Accept: 'application/json' },
                key: 'task-details',
            }, { signal: activeTaskDetailsController.signal });

            if (sequence === requestSequence && activeTaskId === taskId && row.isConnected) {
                renderTask(payload.task);
            }
        } catch (error) {
            if (sequence !== requestSequence || activeTaskId !== taskId || !row.isConnected) {
                return;
            }

            console.error('Task details request failed.', error);
            showToast('error', "Couldn't refresh task details. The cached information is still shown.");
        }
    });

    modal.addEventListener('modal:close', () => {
        activeTaskId = null;
        requestSequence++;
    });
};

document.addEventListener('page:load', initializeTaskDetails);
initializeTaskDetails();
