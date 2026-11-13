import { refreshRegions } from './refresh';
import { showToast } from './toast';
import { request } from './http';

const priorityClasses = {
    High: 'rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-600',
    Medium: 'rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700',
    Low: 'rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700',
};

const statusClasses = {
    Open: 'rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600',
    'In Progress': 'rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700',
    Completed: 'rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700',
};

const nodeClasses = {
    future: 'flex size-11 items-center justify-center rounded-full border-2 border-slate-300 bg-white text-slate-500 transition',
    reached: 'flex size-11 items-center justify-center rounded-full border-2 border-indigo-600 bg-indigo-600 text-white transition',
    current: 'flex size-11 scale-110 items-center justify-center rounded-full border-2 border-indigo-600 bg-white text-indigo-600 ring-4 ring-indigo-500/30 transition',
    completed: 'flex size-11 items-center justify-center rounded-full border-2 border-emerald-600 bg-emerald-600 text-white transition',
    completedCurrent: 'flex size-11 scale-110 items-center justify-center rounded-full border-2 border-emerald-600 bg-emerald-600 text-white ring-4 ring-emerald-500/30 transition',
};

const fillClasses = {
    inProgress: 'h-full rounded-full bg-indigo-500 transition-[width] duration-300',
    completed: 'h-full rounded-full bg-emerald-500 transition-[width] duration-300',
};

let activeProjectModalController;

const setText = (root, selector, value) => {
    const element = root.querySelector(selector);

    if (element) {
        element.textContent = value ?? '';
    }

    return element;
};

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? '—'
        : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date);
};

const initializeProjectModal = () => {
    activeProjectModalController?.abort();
    activeProjectModalController = new AbortController();
    const modal = document.getElementById('project-modal');
    const content = modal?.querySelector('[data-project-modal]');

    if (!modal || !content) {
        return;
    }

    let stages;

    try {
        stages = JSON.parse(content.dataset.progressStages ?? '[]');
    } catch (error) {
        console.error('Could not read the configured progress stages.', error);
        showToast('error', 'Progress stages could not be loaded. Refresh the page.');

        return;
    }

    if (!Array.isArray(stages) || stages.length === 0) {
        console.error('The configured progress stages are empty or malformed.');
        showToast('error', 'Progress stages could not be loaded. Refresh the page.');

        return;
    }

    let currentTask = null;
    let queuedProgress = null;
    let requestInFlight = false;
    let requestSequence = 0;
    let focusedProgress = null;
    let activeActivityFilter = 'all';
    let lastSyncAt = Date.now();

    const stageIndex = (progress) => stages.findIndex((stage) => stage.value === progress);
    const canChangeProgress = () => currentTask?.can?.progress === true;

    const renderProgress = (progress, label, animate = false) => {
        const numericProgress = Number(progress);
        const percent = content.querySelector('[data-pm="percent"]');
        const fill = content.querySelector('[data-pm-progress-fill]');
        const track = content.querySelector('[data-pm-track]');
        const validStageIndex = stageIndex(numericProgress);

        if (!Number.isInteger(numericProgress) || validStageIndex < 0) {
            throw new Error('The task response contains an unsupported progress value.');
        }

        const displayedProgress = Number.parseInt(percent.textContent, 10) || 0;
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const updatePercent = (value) => {
            percent.textContent = `${Math.round(value)}%`;
        };

        if (animate && !prefersReducedMotion && displayedProgress !== numericProgress) {
            const startTime = performance.now();
            const animatePercent = (now) => {
                const fraction = Math.min(1, (now - startTime) / 300);
                updatePercent(displayedProgress + ((numericProgress - displayedProgress) * fraction));

                if (fraction < 1) {
                    window.requestAnimationFrame(animatePercent);
                }
            };

            window.requestAnimationFrame(animatePercent);
        } else {
            updatePercent(numericProgress);
        }

        setText(content, '[data-pm="stage-label"]', label);
        fill.style.width = `${numericProgress}%`;
        fill.className = numericProgress === 100 ? fillClasses.completed : fillClasses.inProgress;
        track.setAttribute('aria-valuenow', String(numericProgress));
        track.setAttribute('aria-valuetext', `${numericProgress}% ${label}`);

        content.querySelectorAll('[data-pm-stage]').forEach((node) => {
            const stageValue = Number(node.dataset.pmStage);
            const nodeIndex = stageIndex(stageValue);
            const isCurrent = stageValue === numericProgress;
            const isReached = nodeIndex < validStageIndex;
            const isComplete = numericProgress === 100;
            const nodeCircle = node.querySelector('[data-pm-stage-node]');
            const checkIcon = node.querySelector('[data-pm-node-check]');
            const stageLabel = node.querySelector('[data-pm-stage-label]');

            node.setAttribute('aria-checked', String(isCurrent));
            node.setAttribute('aria-disabled', String(!canChangeProgress()));
            node.tabIndex = stageValue === (focusedProgress ?? numericProgress) ? 0 : -1;
            node.disabled = !canChangeProgress();
            nodeCircle.className = isComplete && isCurrent
                ? nodeClasses.completedCurrent
                : isCurrent
                    ? nodeClasses.current
                    : isReached
                        ? nodeClasses.reached
                        : nodeClasses.future;
            checkIcon.classList.toggle('hidden', !(isReached || (isComplete && isCurrent)));

            if (stageLabel) {
                stageLabel.classList.toggle('hidden', window.innerWidth < 480 && !isCurrent);
            }
        });

        setText(content, '[data-pm-helper]', 'Click a stage to update your progress. Completing the task on the dashboard sets it to 100% automatically.');
        content.querySelector('[data-pm-helper]').classList.toggle('hidden', !canChangeProgress());
        content.querySelector('[data-pm-locked]').classList.toggle('hidden', canChangeProgress());
        content.querySelector('[data-pm-locked]').classList.toggle('flex', !canChangeProgress());
        content.querySelector('[data-pm-back-stage]').disabled = !canChangeProgress() || validStageIndex === 0 || requestInFlight;
        content.querySelector('[data-pm-next-stage]').disabled = !canChangeProgress() || validStageIndex === stages.length - 1 || requestInFlight;
    };

    const renderActivities = () => {
        const list = content.querySelector('[data-pm-activity-list]');
        const template = content.querySelector('[data-pm-activity-template]');
        const emptyState = content.querySelector('[data-pm-no-activity]');
        const activities = (currentTask?.activities ?? [])
            .filter((activity) => activeActivityFilter === 'all' || activity.kind === activeActivityFilter);

        list.replaceChildren();
        emptyState.classList.toggle('hidden', activities.length > 0);

        activities.forEach((activity) => {
            const row = template.content.firstElementChild.cloneNode(true);
            const timestamp = new Date(activity.timestamp);

            setText(row, '[data-pm-activity-avatar]', activity.initials);
            setText(row, '[data-pm-activity-name]', activity.name);
            setText(row, '[data-pm-activity-text]', activity.text);
            setText(row, '[data-pm-activity-time]', activity.relative_time);
            row.dataset.kind = activity.kind;
            row.querySelector('[data-pm-activity-icon]').classList.toggle('hidden', activity.kind !== 'progress');

            const time = row.querySelector('[data-pm-activity-time]');
            time.title = Number.isNaN(timestamp.getTime()) ? '' : timestamp.toLocaleString();
            time.dateTime = activity.timestamp;
            list.append(row);
        });
    };

    const renderPeople = () => {
        const list = content.querySelector('[data-pm-people]');
        const template = content.querySelector('[data-pm-person-template]');
        const people = currentTask?.people ?? [];

        list.replaceChildren();
        setText(content, '[data-pm-people-count]', String(people.length));
        content.querySelector('[data-pm-one-person]').classList.toggle('hidden', people.length !== 1);

        people.forEach((person) => {
            const row = template.content.firstElementChild.cloneNode(true);

            setText(row, '[data-pm-person-avatar]', person.initials);
            setText(row, '[data-pm-person-name]', person.name);
            setText(row, '[data-pm-person-role]', person.role === 'owner' ? 'Owner' : 'Collaborator');
            row.querySelector('[data-pm-person-me]').classList.toggle('hidden', !person.is_me);
            list.append(row);
        });
    };

    const renderTask = (task, animate = true) => {
        if (!task || !/^\d+$/.test(String(task.id))) {
            throw new Error('The server returned an invalid task.');
        }

        currentTask = task;
        focusedProgress = null;
        activeActivityFilter = 'all';

        setText(content, '[data-pm="title"]', task.title);
        setText(content, '[data-pm="project-name"]', task.project_name);
        setText(content, '[data-pm="description"]', task.description || 'No description added.');

        const priorityBadge = content.querySelector('[data-pm="priority"]');
        priorityBadge.textContent = `${task.priority} priority`;
        priorityBadge.className = priorityClasses[task.priority] ?? priorityClasses.Medium;

        const statusBadge = content.querySelector('[data-pm="status"]');
        statusBadge.textContent = task.status_label;
        statusBadge.className = statusClasses[task.status] ?? statusClasses.Open;

        renderProgress(task.progress, task.progress_label, animate);

        const dueDate = setText(
            content,
            '[data-pm-detail="due"]',
            task.due_overdue ? `Overdue · ${task.due}` : task.due,
        );
        dueDate.classList.toggle('text-red-600', task.due_overdue === true);
        setText(content, '[data-pm-detail="status"]', task.status_label);
        setText(content, '[data-pm-detail="priority"]', `${task.priority} priority`);
        setText(content, '[data-pm-detail="project"]', task.project_name);
        setText(content, '[data-pm-detail="creator"]', task.creator?.name);
        setText(content, '[data-pm-detail="created"]', formatDate(task.created_at));
        setText(content, '[data-pm-detail="updated"]', formatDate(task.updated_at));
        setText(
            content,
            '[data-pm-updated]',
            task.progress_updated_at
                ? `Updated by ${task.progress_updated_by ?? 'Unknown'} · ${new Date(task.progress_updated_at).toLocaleString()}`
                : 'No progress update yet',
        );

        const editButton = content.querySelector('[data-pm-edit]');
        editButton.classList.toggle('hidden', task.can?.edit !== true);
        editButton.classList.toggle('inline-flex', task.can?.edit === true);

        const statusButton = content.querySelector('[data-pm-status]');
        const canChangeStatus = task.can?.change_status === true;
        statusButton.classList.toggle('hidden', !canChangeStatus);
        statusButton.classList.toggle('inline-flex', canChangeStatus);
        statusButton.textContent = task.status === 'Completed' ? 'Reopen' : 'Mark complete';
        statusButton.disabled = requestInFlight;
        statusButton.dataset.nextStatus = task.status === 'Completed' ? 'Open' : 'Completed';

        const commentForm = content.querySelector('[data-pm-comment-form]');
        commentForm.classList.toggle('hidden', task.can?.comment !== true);
        content.querySelector('[data-pm-comment-input]').value = '';
        content.querySelector('[data-pm-comment-send]').disabled = true;
        renderActivities();
        renderPeople();
    };

    const renderOptimisticProgress = (progress) => {
        const stage = stages[stageIndex(progress)];
        const nextStatus = progress === 100 ? 'Completed' : progress === 0 ? 'Open' : 'In Progress';
        const nextStatusLabel = nextStatus === 'Open' ? 'Planning' : nextStatus;
        const statusBadge = content.querySelector('[data-pm="status"]');
        const statusButton = content.querySelector('[data-pm-status]');

        renderProgress(progress, stage.label, true);
        statusBadge.textContent = nextStatusLabel;
        statusBadge.className = statusClasses[nextStatus];
        setText(content, '[data-pm-detail="status"]', nextStatusLabel);
        statusButton.textContent = nextStatus === 'Completed' ? 'Reopen' : 'Mark complete';
        statusButton.dataset.nextStatus = nextStatus === 'Completed' ? 'Open' : 'Completed';
    };

    const updateBusyState = () => {
        content.querySelector('[data-pm-radiogroup]').setAttribute('aria-busy', String(requestInFlight));
        content.querySelector('[data-pm-status]').disabled = requestInFlight;
        content.querySelector('[data-pm-edit]').disabled = requestInFlight;
    };

    const updateHistory = (taskId, push = true) => {
        const currentUrl = new URL(window.location.href);
        const basePath = currentUrl.pathname.replace(/\/\d+\/?$/, '').replace(/\/$/, '') || '/projects';
        const projectPath = `${basePath}/${taskId}`;
        const nextPath = `${projectPath}${currentUrl.search}`;

        if (currentUrl.pathname !== projectPath && push) {
            window.history.pushState({ project: taskId }, '', nextPath);
        }
    };

    const fetchTask = async (taskId, sequence = requestSequence) => {
        const taskUrl = currentTask?.id === Number(taskId) || currentTask?.id === taskId
            ? currentTask.urls?.show
            : `/tasks/${encodeURIComponent(taskId)}`;

        if (!taskUrl) {
            throw new Error('The task details URL is missing.');
        }

        const { data: payload } = await request(taskUrl, {
            headers: { Accept: 'application/json' },
            key: 'project-task-details',
        });

        if (sequence === requestSequence && String(currentTask?.id) === String(taskId)) {
            renderTask(payload.task);
            lastSyncAt = Date.now();
        }

        return payload.task;
    };

    const tryRefreshRegions = async () => {
        try {
            await refreshRegions();
        } catch (error) {
            console.error('Could not refresh the project list after a task update.', error);
            showToast('error', 'The task was updated, but the project cards could not refresh. Reload the page.');
        }
    };

    const submitProgress = async (targetProgress, undo = false) => {
        if (!currentTask || !canChangeProgress()) {
            return;
        }

        if (requestInFlight) {
            queuedProgress = targetProgress;
            renderOptimisticProgress(targetProgress);

            return;
        }

        if (targetProgress === currentTask.progress) {
            return;
        }

        const taskBeforeChange = currentTask;
        const previousProgress = taskBeforeChange.progress;
        const taskId = String(taskBeforeChange.id);
        const sequence = ++requestSequence;
        requestInFlight = true;
        updateBusyState();
        renderOptimisticProgress(targetProgress);

        try {
            const { data: payload } = await request(taskBeforeChange.urls.progress, {
                method: 'PATCH',
                json: {
                    progress: targetProgress,
                    expected_progress: previousProgress,
                },
            });

            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            renderTask(payload.task);
            lastSyncAt = Date.now();
            await tryRefreshRegions();

            const message = undo
                ? payload.message
                : targetProgress === 100
                    ? 'Task completed. Progress set to 100%.'
                    : previousProgress === 100
                        ? 'Task reopened'
                        : `Progress set to ${payload.task.progress}% · ${payload.task.progress_label}`;
            const previousTaskStage = stages[stageIndex(previousProgress)];

            showToast({
                type: 'success',
                title: message,
                actionLabel: undo ? undefined : 'Undo',
                onAction: undo ? undefined : () => {
                    if (String(currentTask?.id) !== taskId) {
                        showToast('info', 'Reopen this task to undo its progress change.');

                        return;
                    }

                    return submitProgress(previousProgress, true);
                },
            });

            if (undo && previousTaskStage) {
                content.querySelector('[data-pm-live]').textContent = `Progress restored to ${payload.task.progress} percent, ${payload.task.progress_label}.`;
            } else {
                content.querySelector('[data-pm-live]').textContent = `Progress set to ${payload.task.progress} percent, ${payload.task.progress_label}.`;
            }
        } catch (error) {
            if (sequence === requestSequence && String(currentTask?.id) === taskId) {
                if (error.status === 409 && error.data?.task) {
                    renderTask(error.data.task);
                    await tryRefreshRegions();
                    showToast('info', 'This task was updated elsewhere. Showing the latest progress.');

                    return;
                }

                currentTask = taskBeforeChange;
                renderTask(currentTask, false);
                console.error('Progress update request failed.', error);
                showToast('error', error.message || 'Could not update progress. Please try again.');
            }
        } finally {
            if (sequence === requestSequence) {
                requestInFlight = false;
                updateBusyState();
                const queuedTarget = queuedProgress;
                queuedProgress = null;

                if (queuedTarget !== null && currentTask && queuedTarget !== currentTask.progress) {
                    void submitProgress(queuedTarget);
                } else if (currentTask) {
                    renderProgress(currentTask.progress, currentTask.progress_label);
                }
            }
        }
    };

    const openProject = (taskId, task = null, pushHistory = true) => {
        if (!/^\d+$/.test(String(taskId))) {
            return;
        }

        requestSequence++;
        queuedProgress = null;
        requestInFlight = false;
        updateBusyState();
        const card = Array.from(document.querySelectorAll('[data-project-card]'))
            .find((projectCard) => projectCard.dataset.projectTaskId === String(taskId));
        let cachedTask = task;

        if (!cachedTask && card) {
            try {
                cachedTask = JSON.parse(card.dataset.task ?? 'null');
            } catch (error) {
                console.error('Could not parse cached project task data.', error);
            }
        }

        if (cachedTask && String(cachedTask.id) === String(taskId)) {
            try {
                renderTask(cachedTask, false);
            } catch (error) {
                console.error('Could not render cached task data.', error);
                showToast('error', "Couldn't open this project. Refresh the page and try again.");
            }
        } else {
            currentTask = { id: Number(taskId), urls: { show: `/tasks/${encodeURIComponent(taskId)}` } };
        }

        if (pushHistory) {
            updateHistory(taskId);
        }

        window.Modal.open(modal.id);
        const sequence = requestSequence;

        void fetchTask(taskId, sequence).catch((error) => {
            if (sequence === requestSequence) {
                console.error('Project task refresh failed.', error);
                showToast('error', "Couldn't refresh this task. The cached information remains available.");
            }
        });
    };

    const refreshCurrentTask = async () => {
        if (!currentTask || modal.classList.contains('hidden')) {
            return;
        }

        const sequence = requestSequence;
        const taskId = String(currentTask.id);

        try {
            await fetchTask(taskId, sequence);
        } catch (error) {
            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            console.error('Could not synchronize the open project task.', error);
            showToast('error', 'Could not synchronize the task. Please try again.');
        }
    };

    const submitStatus = async () => {
        if (!currentTask || currentTask.can?.change_status !== true || requestInFlight) {
            return;
        }

        const previousTask = currentTask;
        const taskId = String(previousTask.id);
        const sequence = requestSequence;
        const nextStatus = content.querySelector('[data-pm-status]').dataset.nextStatus;

        try {
            const { data: payload } = await request(previousTask.urls.status, {
                method: 'PATCH',
                json: { status: nextStatus },
            });

            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            renderTask(payload.task);
            await tryRefreshRegions();
            showToast('success', payload.message ?? 'Task status updated');
        } catch (error) {
            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            console.error('Task status request failed.', error);
            showToast('error', error.message || 'Could not update the task status.');
        }
    };

    const submitComment = async (event) => {
        event.preventDefault();

        if (!currentTask || currentTask.can?.comment !== true) {
            return;
        }

        const task = currentTask;
        const taskId = String(task.id);
        const sequence = requestSequence;
        const input = content.querySelector('[data-pm-comment-input]');
        const body = input.value.trim();

        if (!body) {
            return;
        }

        const sendButton = content.querySelector('[data-pm-comment-send]');
        sendButton.disabled = true;

        try {
            const { data: payload } = await request(task.urls.comments, {
                method: 'POST',
                json: { body },
            });

            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            await refreshCurrentTask();
            await tryRefreshRegions();
            showToast('success', payload.message ?? 'Comment added');
        } catch (error) {
            if (sequence !== requestSequence || String(currentTask?.id) !== taskId) {
                return;
            }

            console.error('Task comment request failed.', error);
            showToast('error', error.message || 'Could not post your comment.');
            input.value = body;
        } finally {
            sendButton.disabled = input.value.trim().length === 0;
        }
    };

    const getTaskIdFromPath = () => {
        const match = window.location.pathname.match(/\/projects\/(\d+)\/?$/);

        return match?.[1] ?? null;
    };

    const showProjectToast = async () => {
        if (!currentTask?.urls?.project) {
            showToast('error', 'This project link is unavailable.');

            return;
        }

        try {
            await navigator.clipboard.writeText(new URL(currentTask.urls.project, window.location.origin).href);
            showToast('success', 'Project link copied');
        } catch (error) {
            console.error('Could not copy the project link.', error);
            showToast('error', 'Could not copy the project link. Check browser clipboard permissions.');
        }
    };

    modal.addEventListener('modal:close', () => {
        const taskId = getTaskIdFromPath();

        if (taskId !== null) {
            const url = new URL(window.location.href);
            const basePath = url.pathname.replace(/\/\d+\/?$/, '').replace(/\/$/, '') || '/projects';

            if (window.history.state?.project === taskId) {
                window.history.back();
            } else {
                window.history.replaceState({}, '', `${basePath}${url.search}`);
            }
        }

        currentTask = null;
        queuedProgress = null;
        requestSequence++;
    });

    modal.addEventListener('modal:beforeclose', (event) => {
        const comment = content.querySelector('[data-pm-comment-input]')?.value.trim();

        if (comment && !window.confirm('Discard your unsent comment?')) {
            event.preventDefault();
        }
    });

    modal.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const stageButton = event.target.closest('[data-pm-stage]');

        if (stageButton) {
            void submitProgress(Number(stageButton.dataset.pmStage));

            return;
        }

        const backButton = event.target.closest('[data-pm-back-stage]');

        if (backButton && currentTask) {
            const index = stageIndex(currentTask.progress);
            void submitProgress(stages[Math.max(0, index - 1)].value);

            return;
        }

        const nextButton = event.target.closest('[data-pm-next-stage]');

        if (nextButton && currentTask) {
            const index = stageIndex(currentTask.progress);
            void submitProgress(stages[Math.min(stages.length - 1, index + 1)].value);

            return;
        }

        const activityFilter = event.target.closest('[data-pm-activity-filter]');

        if (activityFilter) {
            activeActivityFilter = activityFilter.dataset.pmActivityFilter;
            content.querySelectorAll('[data-pm-activity-filter]').forEach((button) => {
                const isActive = button === activityFilter;

                button.setAttribute('aria-pressed', String(isActive));
                button.classList.toggle('bg-white', isActive);
                button.classList.toggle('text-slate-900', isActive);
                button.classList.toggle('shadow-sm', isActive);
                button.classList.toggle('text-slate-500', !isActive);
            });
            renderActivities();

            return;
        }

        if (event.target.closest('[data-pm-status]')) {
            void submitStatus();

            return;
        }

        if (event.target.closest('[data-pm-edit]') && currentTask) {
            const taskId = currentTask.id;

            if (window.Modal.close(modal.id)) {
                document.dispatchEvent(new CustomEvent('task:edit', { detail: { taskId } }));
            }

            return;
        }

        if (event.target.closest('[data-pm-copy-link]')) {
            void showProjectToast();
        }
    });

    content.querySelector('[data-pm-comment-form]').addEventListener('submit', (event) => {
        void submitComment(event);
    });
    content.querySelector('[data-pm-comment-input]').addEventListener('input', (event) => {
        content.querySelector('[data-pm-comment-send]').disabled = event.target.value.trim().length === 0;
    });
    content.querySelectorAll('[data-pm-activity-filter]').forEach((button) => {
        button.setAttribute('aria-pressed', String(button.dataset.pmActivityFilter === 'all'));
    });

    content.querySelector('[data-pm-track]').addEventListener('click', (event) => {
        if (event.target.closest('[data-pm-stage]') || !canChangeProgress()) {
            return;
        }

        const track = event.currentTarget.getBoundingClientRect();
        const ratio = Math.max(0, Math.min(1, (event.clientX - track.left) / track.width));
        const nearestStage = stages.reduce((nearest, stage) => (
            Math.abs(stage.value / 100 - ratio) < Math.abs(nearest.value / 100 - ratio) ? stage : nearest
        ));

        void submitProgress(nearestStage.value);
    });

    content.querySelector('[data-pm-radiogroup]').addEventListener('keydown', (event) => {
        if (!(event.target instanceof Element) || !event.target.matches('[data-pm-stage]')) {
            return;
        }

        const currentIndex = stageIndex(Number(event.target.dataset.pmStage));
        let nextIndex = currentIndex;

        if (event.key === 'ArrowRight') {
            nextIndex = Math.min(stages.length - 1, currentIndex + 1);
        } else if (event.key === 'ArrowLeft') {
            nextIndex = Math.max(0, currentIndex - 1);
        } else if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = stages.length - 1;
        } else if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            void submitProgress(Number(event.target.dataset.pmStage));

            return;
        } else {
            return;
        }

        event.preventDefault();
        focusedProgress = stages[nextIndex].value;
        content.querySelector(`[data-pm-stage="${stages[nextIndex].value}"]`).focus();
        renderProgress(currentTask?.progress ?? 0, currentTask?.progress_label ?? stages[0].label);
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const opener = event.target.closest('[data-project-open]');

        if (opener) {
            const card = opener.closest('[data-project-card]');
            const taskId = card?.dataset.projectTaskId ?? opener.dataset.projectOpen;

            if (taskId) {
                event.preventDefault();
                openProject(taskId);
            }
        }
    }, { signal: activeProjectModalController.signal });

    document.addEventListener('keydown', (event) => {
        if (!(event.target instanceof Element) || !event.target.matches('[data-project-card]')) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openProject(event.target.dataset.projectTaskId);
        }
    }, { signal: activeProjectModalController.signal });

    window.addEventListener('popstate', () => {
        const taskId = getTaskIdFromPath();

        if (taskId === null) {
            if (!modal.classList.contains('hidden')) {
                window.Modal.close(modal.id);
            }

            return;
        }

        openProject(taskId, null, false);
    }, { signal: activeProjectModalController.signal });

    window.addEventListener('focus', () => {
        if (Date.now() - lastSyncAt > 15000) {
            void tryRefreshRegions();
            void refreshCurrentTask();
        }
    }, { signal: activeProjectModalController.signal });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && Date.now() - lastSyncAt > 15000) {
            void tryRefreshRegions();
            void refreshCurrentTask();
        }
    }, { signal: activeProjectModalController.signal });
    window.addEventListener('resize', () => {
        if (currentTask) {
            renderProgress(currentTask.progress, currentTask.progress_label);
        }
    }, { signal: activeProjectModalController.signal });

    const initialData = document.getElementById('project-modal-data');
    const pathTaskId = getTaskIdFromPath();

    if (initialData && pathTaskId !== null) {
        try {
            const task = JSON.parse(initialData.textContent);

            if (String(task.id) === pathTaskId) {
                openProject(pathTaskId, task, false);
            }
        } catch (error) {
            console.error('Could not read the task embedded in this project page.', error);
            showToast('error', "Couldn't open this project. Refresh the page and try again.");
        }
    }
};

document.addEventListener('page:load', initializeProjectModal);
initializeProjectModal();
