const initializeDashboardTaskFilters = () => {
    const controls = document.querySelector('[data-dashboard-task-controls]');
    const taskList = document.getElementById('task-list');

    if (!controls || !taskList) {
        return;
    }

    const sortSelect = controls.querySelector('[data-dashboard-task-sort]');
    const statusButtons = Array.from(controls.querySelectorAll('[data-dashboard-status-filter]'));
    const priorityButtons = Array.from(controls.querySelectorAll('[data-dashboard-priority-filter]'));
    const listEmptyState = taskList.querySelector('[data-task-list-empty]');
    const filterEmptyState = taskList.querySelector('[data-task-filter-empty]');
    const searchTerm = new URLSearchParams(window.location.search)
        .get('q')
        ?.trim()
        .toLocaleLowerCase() ?? '';
    let activeStatus = 'all';
    let activePriority = 'all';

    const setActiveFilter = (buttons, activeButton) => {
        buttons.forEach((button) => {
            const isActive = button === activeButton;

            button.setAttribute('aria-pressed', String(isActive));
            button.classList.toggle('bg-white', isActive);
            button.classList.toggle('text-slate-900', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('text-slate-500', !isActive);
        });
    };

    const sortTasks = (rows) => {
        const priorityOrder = { High: 0, Medium: 1, Low: 2 };
        const sortBy = sortSelect instanceof HTMLSelectElement ? sortSelect.value : 'due_date';

        rows.sort((first, second) => {
            if (sortBy === 'priority') {
                return (priorityOrder[first.dataset.taskPriority] ?? 3)
                    - (priorityOrder[second.dataset.taskPriority] ?? 3);
            }

            if (sortBy === 'title') {
                return (first.dataset.taskTitle ?? '').localeCompare(second.dataset.taskTitle ?? '');
            }

            return (first.dataset.taskDueDate ?? '').localeCompare(second.dataset.taskDueDate ?? '');
        });

        rows.forEach((row) => taskList.insertBefore(row, listEmptyState ?? filterEmptyState));
    };

    const updateTasks = () => {
        const rows = Array.from(taskList.querySelectorAll('[data-task-row]'));
        let visibleCount = 0;

        sortTasks(rows);

        rows.forEach((row) => {
            const matchesStatus = activeStatus === 'all' || row.dataset.taskStatus === activeStatus;
            const matchesPriority = activePriority === 'all' || row.dataset.taskPriority === activePriority;
            const matchesSearch = (row.textContent ?? '').toLocaleLowerCase().includes(searchTerm);
            const isVisible = matchesStatus && matchesPriority && matchesSearch;

            row.classList.toggle('hidden', !isVisible);
            row.setAttribute('aria-hidden', String(!isVisible));
            visibleCount += Number(isVisible);
        });

        listEmptyState?.classList.toggle('hidden', rows.length > 0);
        filterEmptyState?.classList.toggle('hidden', rows.length === 0 || visibleCount > 0);
    };

    statusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeStatus = button.dataset.dashboardStatusFilter ?? 'all';
            setActiveFilter(statusButtons, button);
            updateTasks();
        });
    });

    priorityButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activePriority = button.dataset.dashboardPriorityFilter ?? 'all';
            setActiveFilter(priorityButtons, button);
            updateTasks();
        });
    });

    sortSelect?.addEventListener('change', updateTasks);
    taskList.addEventListener('dashboard:tasks-changed', updateTasks);
    updateTasks();
};

document.addEventListener('page:load', initializeDashboardTaskFilters);
initializeDashboardTaskFilters();
