import { showToast } from './toast';
import { updateDashboardTaskStats } from './task-stats';

const initializeTaskForm = () => {
    const form = document.getElementById('create-task-form');

    if (!form) {
        return;
    }

    const successModal = document.getElementById('task-success-modal');
    const successViewTaskButton = successModal?.querySelector('[data-success-view-task]');
    const modal = document.getElementById('create-task-modal');
    const taskList = document.getElementById('task-list');
    const projectsRoot = document.querySelector('[data-projects-root]');
    const submitButton = document.getElementById('create-task-submit');
    const submitLabel = document.getElementById('create-task-submit-label');
    const modalTitle = document.getElementById('create-task-modal-title');
    const modalSubtitle = document.getElementById('create-task-modal-desc');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const titleCounter = document.getElementById('title-counter');
    const descriptionCounter = document.getElementById('description-counter');
    const dueDateInput = document.getElementById('due_date');
    const dueDateMinimum = dueDateInput.min;
    const methodOverride = document.getElementById('task-method-override');
    const statusHelp = document.getElementById('status-help');
    const taskFormNote = document.getElementById('task-form-note');
    const assigneeToggle = document.getElementById('assignee-toggle');
    const assigneePopover = document.getElementById('assignee-popover');
    const assigneeAvatars = document.getElementById('assignee-avatars');
    const assigneeHiddenInputs = document.getElementById('assignee-hidden-inputs');
    const touchedFields = new Set();
    const fieldNames = ['title', 'description', 'project', 'priority', 'status', 'due_date', 'assignees'];
    const focusableField = {
        title: titleInput,
        description: descriptionInput,
        project: document.getElementById('project'),
        priority: document.getElementById('priority'),
        status: document.getElementById('status'),
        due_date: dueDateInput,
        assignees: assigneeToggle,
    };
    const fieldInputs = {
        title: titleInput,
        description: descriptionInput,
        project: document.getElementById('project'),
        priority: document.getElementById('priority'),
        status: document.getElementById('status'),
        due_date: dueDateInput,
        assignees: assigneeToggle,
    };
    const fieldStyleTargets = {
        ...fieldInputs,
        assignees: document.getElementById('assignee-box'),
    };
    let isSubmitting = false;
    let currentMode = 'create';
    let editingRow = null;
    let initialFormState = '';
    let createdTaskRow = null;
    let refreshProjectsAfterCreate = false;

    // Keep select validation tied to the server-rendered options.
    const isOptionValue = (fieldName, value) => {
        const select = fieldInputs[fieldName];

        return Array.from(select.options).some((option) => option.value === value);
    };

    const getTodayString = () => {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    // Return the first validation message for one field, or an empty string.
    const getValidationMessage = (fieldName) => {
        switch (fieldName) {
            case 'title': {
                const title = titleInput.value.trim();

                if (title.length < 3) {
                    return 'Task name must be at least 3 characters.';
                }

                if (title.length > 120) {
                    return 'Task name must be 120 characters or fewer.';
                }

                return '';
            }
            case 'description':
                return descriptionInput.value.length > 500
                    ? 'Description must be 500 characters or fewer.'
                    : '';
            case 'project':
                return isOptionValue('project', fieldInputs.project.value) ? '' : 'Please choose a project.';
            case 'priority':
                return isOptionValue('priority', fieldInputs.priority.value) ? '' : 'Please choose a priority.';
            case 'status':
                return isOptionValue('status', fieldInputs.status.value) ? '' : 'Please choose a status.';
            case 'due_date':
                return currentMode === 'create' && dueDateInput.value && dueDateInput.value < getTodayString()
                    ? "Due date can't be in the past."
                    : '';
            default:
                return '';
        }
    };

    // Apply accessible error state and restore the neutral field styling.
    const setFieldError = (fieldName, message) => {
        const input = fieldInputs[fieldName];
        const styleTarget = fieldStyleTargets[fieldName];
        const error = document.getElementById(`${fieldName}-error`);
        const help = document.getElementById(`${fieldName}-help`);

        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', `${help.id} ${error.id}`);
        styleTarget.classList.remove('border-slate-200', 'focus:border-indigo-500', 'focus:ring-indigo-500/20');
        styleTarget.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500/20');
        error.textContent = message;
        error.classList.remove('hidden');
    };

    const clearFieldError = (fieldName) => {
        const input = fieldInputs[fieldName];
        const styleTarget = fieldStyleTargets[fieldName];
        const error = document.getElementById(`${fieldName}-error`);
        const help = document.getElementById(`${fieldName}-help`);

        input.removeAttribute('aria-invalid');
        input.setAttribute('aria-describedby', help.id);
        styleTarget.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500/20');
        styleTarget.classList.add('border-slate-200', 'focus:border-indigo-500', 'focus:ring-indigo-500/20');
        error.textContent = '';
        error.classList.add('hidden');
    };

    const validateField = (fieldName) => {
        const message = getValidationMessage(fieldName);

        if (message) {
            setFieldError(fieldName, message);

            return false;
        }

        clearFieldError(fieldName);

        return true;
    };

    // Keep live counters in sync with the current field values.
    const updateCounters = () => {
        titleCounter.textContent = `${titleInput.value.length}/120`;
        descriptionCounter.textContent = `${descriptionInput.value.length}/500`;
    };

    const updateTouchedField = (fieldName) => {
        if (touchedFields.has(fieldName)) {
            validateField(fieldName);
        } else if (getValidationMessage(fieldName) === '') {
            clearFieldError(fieldName);
        }
    };

    titleInput.addEventListener('blur', () => {
        touchedFields.add('title');
        validateField('title');
    });
    titleInput.addEventListener('input', () => {
        updateCounters();
        updateTouchedField('title');
    });

    descriptionInput.addEventListener('blur', () => {
        touchedFields.add('description');
        validateField('description');
    });
    descriptionInput.addEventListener('input', () => {
        updateCounters();
        updateTouchedField('description');
    });

    ['project', 'priority', 'status'].forEach((fieldName) => {
        const input = fieldInputs[fieldName];

        input.addEventListener('blur', () => {
            touchedFields.add(fieldName);
            validateField(fieldName);
        });
        input.addEventListener('change', () => updateTouchedField(fieldName));
    });

    // Date presets use local calendar dates rather than UTC conversions.
    const quickDateButtons = Array.from(document.querySelectorAll('[data-quick-date]'));
    const resetQuickDateButtons = () => {
        quickDateButtons.forEach((button) => {
            button.classList.remove('border-indigo-200', 'bg-indigo-50', 'text-indigo-700');
            button.classList.add('border-slate-200', 'bg-white', 'text-slate-600');
            button.setAttribute('aria-pressed', 'false');
        });
    };

    dueDateInput.addEventListener('blur', () => {
        touchedFields.add('due_date');
        validateField('due_date');
    });
    dueDateInput.addEventListener('input', () => {
        resetQuickDateButtons();
        updateTouchedField('due_date');
    });
    dueDateInput.addEventListener('change', () => {
        resetQuickDateButtons();
        updateTouchedField('due_date');
    });

    quickDateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const date = new Date();
            date.setDate(date.getDate() + Number(button.dataset.quickDate));
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            dueDateInput.value = `${year}-${month}-${day}`;
            resetQuickDateButtons();
            button.classList.remove('border-slate-200', 'bg-white', 'text-slate-600');
            button.classList.add('border-indigo-200', 'bg-indigo-50', 'text-indigo-700');
            button.setAttribute('aria-pressed', 'true');
            touchedFields.add('due_date');
            validateField('due_date');
        });
    });

    // Keep the selected avatars, hidden submitted ids, and options synchronized.
    const syncAssignees = () => {
        const options = Array.from(assigneePopover.querySelectorAll('[data-assignee-option]'));
        const selectedOptions = options.filter((option) => option.querySelector('[data-assignee-checkbox]').checked);

        assigneeAvatars.replaceChildren();
        assigneeHiddenInputs.replaceChildren();

        selectedOptions.forEach((option) => {
            const checkbox = option.querySelector('[data-assignee-checkbox]');
            const memberId = option.dataset.assigneeId;
            const initials = option.dataset.assigneeInitials;
            const name = option.dataset.assigneeName;
            const avatar = document.createElement('button');
            const hiddenInput = document.createElement('input');

            avatar.type = 'button';
            avatar.className = 'flex size-7 items-center justify-center rounded-full border border-white bg-slate-200 text-[9px] font-bold text-slate-600 hover:bg-red-100 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/40';
            avatar.dataset.assigneeRemove = memberId;
            avatar.setAttribute('aria-label', `Remove ${name}`);
            avatar.title = `${name} (click to remove)`;
            avatar.textContent = initials;
            assigneeAvatars.append(avatar);

            hiddenInput.type = 'hidden';
            hiddenInput.name = 'assignees[]';
            hiddenInput.value = memberId;
            hiddenInput.dataset.assigneeHidden = '';
            assigneeHiddenInputs.append(hiddenInput);

            option.setAttribute('aria-selected', checkbox.checked ? 'true' : 'false');
        });
    };

    const closeAssigneePopover = () => {
        assigneePopover.hidden = true;
        assigneeToggle.setAttribute('aria-expanded', 'false');
    };

    assigneeToggle.addEventListener('click', () => {
        assigneePopover.hidden = !assigneePopover.hidden;
        assigneeToggle.setAttribute('aria-expanded', assigneePopover.hidden ? 'false' : 'true');
    });

    assigneePopover.addEventListener('change', (event) => {
        if (event.target.matches('[data-assignee-checkbox]')) {
            syncAssignees();
            updateTouchedField('assignees');
        }
    });

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element) || assigneePopover.hidden) {
            return;
        }

        const target = event.target;

        if (assigneePopover.contains(target) || assigneeToggle.contains(target)) {
            return;
        }

        closeAssigneePopover();

        if (target.closest('[data-modal-close]') || target === modal) {
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const removeButton = event.target.closest('[data-assignee-remove]');

        if (!removeButton) {
            return;
        }

        const memberId = removeButton.dataset.assigneeRemove;
        const checkbox = Array.from(assigneePopover.querySelectorAll('[data-assignee-checkbox]'))
            .find((input) => input.value === memberId);

        if (checkbox) {
            checkbox.checked = false;
            syncAssignees();
            updateTouchedField('assignees');
        }
    });

    // Let Escape dismiss the assignee list before the surrounding modal.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !assigneePopover.hidden) {
            event.preventDefault();
            event.stopPropagation();
            closeAssigneePopover();
        }
    }, true);

    // Ask before discarding edits, then restore defaults after any completed close.
    const serializeFormState = () => JSON.stringify(Array.from(form.elements)
        .filter((element) => element.name && element.type !== 'submit' && element.type !== 'button')
        .map((element) => ({
            name: element.name,
            type: element.type,
            value: element.value,
            checked: 'checked' in element ? element.checked : null,
        })));

    const isDirty = () => serializeFormState() !== initialFormState;

    modal.addEventListener('modal:beforeclose', (event) => {
        if (isSubmitting && form.dataset.skipDiscardConfirmation !== 'true') {
            event.preventDefault();

            return;
        }

        if (
            form.dataset.skipDiscardConfirmation !== 'true'
            && isDirty()
            && !window.confirm('Discard this task? Your changes will be lost.')
        ) {
            event.preventDefault();
        }
    });

    successModal?.addEventListener('modal:close', () => {
        const shouldRefreshProjects = refreshProjectsAfterCreate;
        refreshProjectsAfterCreate = false;
        createdTaskRow = null;
        if (successViewTaskButton) {
            successViewTaskButton.textContent = 'View Task';
        }

        if (shouldRefreshProjects) {
            window.location.reload();
        }
    });

    successViewTaskButton?.addEventListener('click', () => {
        const taskRow = createdTaskRow;
        const shouldRefreshProjects = refreshProjectsAfterCreate;

        if (!window.Modal.close(successModal.id)) {
            console.error('The task success dialog could not close.');

            return;
        }

        if (shouldRefreshProjects) {
            return;
        }

        if (!taskRow?.isConnected) {
            if (taskRow && form.dataset.dashboardUrl) {
                const dashboardUrl = new URL(form.dataset.dashboardUrl, window.location.origin);
                dashboardUrl.hash = `task-row-${taskRow.dataset.taskId}`;
                window.location.assign(dashboardUrl);
            }

            return;
        }

        taskRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        taskRow.querySelector('[data-edit-task]')?.focus({ preventScroll: true });
    });

    const showCreateSuccess = (taskRow) => {
        const shouldRefreshProjects = Boolean(
            projectsRoot
            && taskRow.dataset.taskPriority === 'High',
        );

        if (!successModal || !successViewTaskButton) {
            console.error('The task success dialog is missing from the page.');
            showToast('success', 'Task created', taskRow.dataset.taskTitle ?? '');

            if (shouldRefreshProjects) {
                window.setTimeout(() => window.location.reload(), 1000);
            }

            return;
        }

        createdTaskRow = taskRow;
        refreshProjectsAfterCreate = shouldRefreshProjects;
        successViewTaskButton.textContent = shouldRefreshProjects ? 'View project' : 'View Task';

        if (!window.Modal.open(successModal.id)) {
            createdTaskRow = null;
            refreshProjectsAfterCreate = false;
            successViewTaskButton.textContent = 'View Task';
            console.error('The task success dialog could not be opened.');
            showToast('success', 'Task created', taskRow.dataset.taskTitle ?? '');

            if (shouldRefreshProjects) {
                window.setTimeout(() => window.location.reload(), 1000);
            }
        }
    };

    const setSubmitting = (submitting) => {
        isSubmitting = submitting;
        submitButton.disabled = submitting;
        submitLabel.textContent = submitting
            ? (currentMode === 'edit' ? 'Saving…' : 'Creating…')
            : (currentMode === 'edit' ? 'Save changes' : 'Create task');
    };

    const setFormMode = (mode) => {
        currentMode = mode;
        const isEditing = mode === 'edit';

        modalTitle.textContent = isEditing ? 'Edit task' : 'Create a new task';
        modalSubtitle.textContent = isEditing
            ? 'Update the details and keep everyone aligned.'
            : 'Add a clear, actionable item to your list.';
        submitLabel.textContent = isEditing ? 'Save changes' : 'Create task';
        statusHelp.textContent = isEditing
            ? 'Update the current task status.'
            : 'New tasks default to Open until they are marked as In Progress.';
        taskFormNote.textContent = isEditing
            ? 'Your changes will be visible to teammates after you save.'
            : 'Add the task details you want to track and save when you\'re ready.';
        methodOverride.value = isEditing ? 'PATCH' : '';
        form.action = isEditing ? form.dataset.updateUrl : form.dataset.createAction;
        dueDateInput.min = isEditing ? '' : dueDateMinimum;
        document.querySelectorAll('[data-create-counter]').forEach((counter) => {
            counter.hidden = isEditing;
        });
    };

    const showServerErrors = (errors) => {
        fieldNames.forEach(clearFieldError);

        const normalizedErrors = { ...errors };

        Object.keys(errors).forEach((fieldName) => {
            if (fieldName.startsWith('assignees.')) {
                normalizedErrors.assignees ??= errors[fieldName];
            }
        });

        const invalidFields = fieldNames.filter((fieldName) => {
            const messages = normalizedErrors[fieldName];

            if (!Array.isArray(messages) || messages.length === 0) {
                return false;
            }

            touchedFields.add(fieldName);
            setFieldError(fieldName, messages[0]);

            return true;
        });

        focusableField[invalidFields[0]]?.focus();
    };

    const resetForm = () => {
        form.reset();
        editingRow = null;
        delete form.dataset.updateUrl;
        setFormMode('create');
        touchedFields.clear();
        fieldNames.forEach(clearFieldError);
        resetQuickDateButtons();
        closeAssigneePopover();
        syncAssignees();
        updateCounters();
        setSubmitting(false);
        initialFormState = serializeFormState();
    };

    const openEditForm = (row) => {
        const taskId = row.dataset.taskId;

        if (!taskId) {
            console.error('Cannot edit a task without a task id.');
            showToast('error', "Couldn't open this task. Please try again.");

            return;
        }

        resetForm();
        editingRow = row;
        form.dataset.updateUrl = form.dataset.updateUrlTemplate.replace(
            '__task_id__',
            encodeURIComponent(taskId),
        );
        setFormMode('edit');
        titleInput.value = row.dataset.taskTitle ?? '';
        descriptionInput.value = row.dataset.taskDescription ?? '';
        fieldInputs.project.value = row.dataset.taskProjectId ?? '';
        fieldInputs.priority.value = row.dataset.taskPriority ?? '';
        fieldInputs.status.value = row.dataset.taskStatus ?? '';
        dueDateInput.value = row.dataset.taskDueDate ?? '';

        const selectedAssignees = new Set(
            (row.dataset.taskAssignees ?? '').split(',').filter(Boolean),
        );
        assigneePopover.querySelectorAll('[data-assignee-checkbox]').forEach((checkbox) => {
            checkbox.checked = selectedAssignees.has(checkbox.value);
        });

        touchedFields.clear();
        fieldNames.forEach(clearFieldError);
        resetQuickDateButtons();
        syncAssignees();
        updateCounters();
        initialFormState = serializeFormState();
        window.Modal.open(modal.id);
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const editButton = event.target.closest('[data-edit-task]');

        if (!editButton) {
            return;
        }

        event.preventDefault();
        const row = editButton.closest('[data-task-row]');

        if (row) {
            openEditForm(row);
        }
    });

    modal.addEventListener('modal:close', resetForm);

    // Capture the initial defaults after hidden assignee inputs are in sync.
    syncAssignees();
    updateCounters();
    initialFormState = serializeFormState();

    // Validate before sending; use JSON responses for field-level server errors.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        fieldNames.forEach((fieldName) => touchedFields.add(fieldName));
        const invalidFields = fieldNames.filter((fieldName) => !validateField(fieldName));

        if (invalidFields.length > 0) {
            focusableField[invalidFields[0]].focus();

            return;
        }

        titleInput.value = titleInput.value.trim();
        updateCounters();
        const taskTitle = titleInput.value;
        const taskStatus = fieldInputs.status.value;
        const isEditing = currentMode === 'edit';
        const rowToReplace = editingRow;
        const previousStatus = isEditing ? rowToReplace.dataset.taskStatus : null;
        const failureMessage = isEditing
            ? "Couldn't save changes. Please try again."
            : "Couldn't create the task. Please try again.";
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            showToast('error', failureMessage);
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
        } catch (error) {
            console.error('Task save request failed.', error);
            showToast('error', failureMessage);
            setSubmitting(false);

            return;
        }

        if (response.status === 422) {
            try {
                const payload = await response.json();
                showServerErrors(payload.errors ?? {});
            } catch (error) {
                console.error('Could not read task validation errors.', error);
                showToast('error', failureMessage);
            }

            setSubmitting(false);

            return;
        }

        if (!response.ok) {
            showToast('error', failureMessage);
            setSubmitting(false);

            return;
        }

        let payload;

        try {
            payload = await response.json();
        } catch (error) {
            console.error('Could not read the task save response.', error);
            showToast('error', failureMessage);
            setSubmitting(false);

            return;
        }

        const rowMarkup = document.createElement('template');
        rowMarkup.innerHTML = typeof payload.html === 'string' ? payload.html : '';
        const returnedRow = rowMarkup.content.firstElementChild;

        if (
            !returnedRow?.matches('[data-task-row]')
            || rowMarkup.content.childElementCount !== 1
            || (isEditing && (!taskList || !rowToReplace?.isConnected))
        ) {
            console.error('The task save response did not contain a replaceable task row.');
            showToast('error', failureMessage);
            setSubmitting(false);

            return;
        }

        form.dataset.skipDiscardConfirmation = 'true';
        const modalClosed = window.Modal.close(modal.id);
        delete form.dataset.skipDiscardConfirmation;

        if (!modalClosed) {
            console.error('The task modal could not close after a successful save.');
            showToast('error', failureMessage);
            setSubmitting(false);

            return;
        }

        if (taskList) {
            if (isEditing) {
                rowToReplace.replaceWith(returnedRow);
                returnedRow.querySelector('[data-edit-task]')?.focus();
            } else {
                taskList.prepend(returnedRow);
            }

            taskList.dispatchEvent(new CustomEvent('dashboard:tasks-changed'));
            returnedRow.classList.add('transition-colors', 'duration-1000', 'bg-indigo-50/60');
            window.setTimeout(() => returnedRow.classList.remove('bg-indigo-50/60'), 2500);

            updateDashboardTaskStats(previousStatus, taskStatus, isEditing ? 0 : 1);
        }

        if (isEditing) {
            showToast('success', payload.message ?? 'Task updated', taskTitle);
        } else {
            showCreateSuccess(returnedRow);
        }
    });

    // Ctrl/Cmd+Enter submits the task form without requiring the button.
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
};

initializeTaskForm();
