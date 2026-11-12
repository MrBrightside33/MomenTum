const initializeProjects = () => {
    const root = document.querySelector('[data-projects-root]');

    if (!root) {
        return;
    }

    const projectCards = Array.from(root.querySelectorAll('[data-project-card]'));
    const filterButtons = Array.from(root.querySelectorAll('[data-project-filter]'));
    const searchInput = root.querySelector('[data-project-search]');
    const emptyState = root.querySelector('[data-projects-empty]');
    const resetButton = root.querySelector('[data-projects-reset]');
    const resultCount = root.querySelector('[data-projects-result-count]');
    let activeFilter = 'all';

    const updateProjects = () => {
        const searchTerm = searchInput instanceof HTMLInputElement
            ? searchInput.value.trim().toLocaleLowerCase()
            : '';
        let visibleCount = 0;

        projectCards.forEach((card) => {
            const matchesFilter = activeFilter === 'all' || card.dataset.projectStatus === activeFilter;
            const searchableText = (card.dataset.projectSearch ?? card.textContent).toLocaleLowerCase();
            const isVisible = matchesFilter && searchableText.includes(searchTerm);

            card.classList.toggle('hidden', !isVisible);
            card.setAttribute('aria-hidden', String(!isVisible));
            visibleCount += Number(isVisible);
        });

        const counts = {
            all: projectCards.length,
            in_progress: projectCards.filter((card) => card.dataset.projectStatus === 'in_progress').length,
            completed: projectCards.filter((card) => card.dataset.projectStatus === 'completed').length,
            planning: projectCards.filter((card) => card.dataset.projectStatus === 'planning').length,
        };

        Object.entries(counts).forEach(([filter, count]) => {
            const countElement = root.querySelector(`[data-project-filter-count="${filter}"]`);

            if (countElement) {
                countElement.textContent = String(count);
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
            emptyState.classList.toggle('flex', visibleCount === 0);
        }

        if (resultCount) {
            resultCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'project' : 'projects'}.`;
        }
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.projectFilter ?? 'all';

            filterButtons.forEach((filterButton) => {
                const isActive = filterButton === button;

                filterButton.setAttribute('aria-pressed', String(isActive));
                filterButton.classList.toggle('bg-white', isActive);
                filterButton.classList.toggle('text-slate-900', isActive);
                filterButton.classList.toggle('shadow-sm', isActive);
                filterButton.classList.toggle('text-slate-500', !isActive);
            });

            updateProjects();
        });
    });

    searchInput?.addEventListener('input', updateProjects);
    resetButton?.addEventListener('click', () => {
        if (searchInput instanceof HTMLInputElement) {
            searchInput.value = '';
        }

        const allProjectsButton = filterButtons.find((button) => button.dataset.projectFilter === 'all');

        if (allProjectsButton instanceof HTMLButtonElement) {
            allProjectsButton.click();
        } else {
            updateProjects();
        }
    });

    updateProjects();
};

initializeProjects();
