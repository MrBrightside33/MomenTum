export const updateDashboardTaskStats = (previousStatus, nextStatus, totalChange) => {
    const statElement = (key) => {
        const element = document.querySelector(`[data-stat="${key}"]`);

        if (!element) {
            throw new Error(`Missing dashboard stat: ${key}`);
        }

        return element;
    };
    const totalElement = statElement('total');
    const inProgressElement = statElement('in_progress');
    const completedElement = statElement('completed');
    const total = Number.parseInt(totalElement.textContent, 10) + totalChange;
    const inProgress = Number.parseInt(inProgressElement.textContent, 10)
        + Number(nextStatus === 'In Progress')
        - Number(previousStatus === 'In Progress');
    const completed = Number.parseInt(completedElement.textContent, 10)
        + Number(nextStatus === 'Completed')
        - Number(previousStatus === 'Completed');

    if (![total, inProgress, completed].every(Number.isFinite) || total < 0 || inProgress < 0 || completed < 0) {
        throw new Error('Dashboard task stats must contain valid non-negative counts.');
    }

    totalElement.textContent = String(total);
    inProgressElement.textContent = String(inProgress);
    completedElement.textContent = String(completed);

    const totalNote = document.querySelector('[data-stat-note="total"]');
    const completedNote = document.querySelector('[data-stat-note="completed"]');

    if (!totalNote || !completedNote) {
        throw new Error('Missing dashboard stat note.');
    }

    totalNote.textContent = `${total - inProgress - completed} open`;
    completedNote.textContent = `${completed} completed`;
};
