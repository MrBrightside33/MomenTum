let activeProjectsController;

const initializeProjects = () => {
    activeProjectsController?.abort();
    activeProjectsController = new AbortController();
    const root = document.querySelector('[data-projects-root]');

    if (!root) {
        return;
    }

    let activeFilter = root.querySelector('[data-project-filter][aria-pressed="true"]')?.dataset.projectFilter ?? 'all';

    const updateProjects = () => {
        const searchInput = root.querySelector('[data-project-search]');
        const projectCards = Array.from(root.querySelectorAll('[data-project-card]'));
        const filterButtons = Array.from(root.querySelectorAll('[data-project-filter]'));
        const emptyState = root.querySelector('[data-projects-empty]');
        const resultCount = root.querySelector('[data-projects-result-count]');
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

        filterButtons.forEach((button) => {
            const isActive = button.dataset.projectFilter === activeFilter;

            button.setAttribute('aria-pressed', String(isActive));
            button.classList.toggle('bg-white', isActive);
            button.classList.toggle('text-slate-900', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('text-slate-500', !isActive);
        });
    };

    root.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const filterButton = event.target.closest('[data-project-filter]');

        if (filterButton) {
            activeFilter = filterButton.dataset.projectFilter ?? 'all';
            updateProjects();

            return;
        }

        if (event.target.closest('[data-projects-reset]')) {
            const searchInput = root.querySelector('[data-project-search]');

            if (searchInput instanceof HTMLInputElement) {
                searchInput.value = '';
            }

            activeFilter = 'all';
            updateProjects();
        }
    });

    root.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.matches('[data-project-search]')) {
            updateProjects();
        }
    });

    document.addEventListener('regions:refreshed', initializeProjects, { signal: activeProjectsController.signal });
    updateProjects();
};

document.addEventListener('page:load', initializeProjects);
initializeProjects();
