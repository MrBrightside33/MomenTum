const initializeTeam = () => {
    const root = document.querySelector('[data-team-root]');

    if (!root) {
        return;
    }

    const teamCards = Array.from(root.querySelectorAll('[data-team-card]'));
    const filterButtons = Array.from(root.querySelectorAll('[data-team-filter]'));
    const searchInput = root.querySelector('[data-team-search]');
    const emptyState = root.querySelector('[data-team-empty]');
    const resetButton = root.querySelector('[data-team-reset]');
    const resultCount = root.querySelector('[data-team-result-count]');
    let activeFilter = 'all';

    const updateTeam = () => {
        const searchTerm = searchInput instanceof HTMLInputElement
            ? searchInput.value.trim().toLocaleLowerCase()
            : '';
        let visibleCount = 0;

        teamCards.forEach((card) => {
            const matchesFilter = activeFilter === 'all' || card.dataset.teamRole === activeFilter;
            const searchableText = (card.dataset.teamSearch ?? card.textContent).toLocaleLowerCase();
            const isVisible = matchesFilter && searchableText.includes(searchTerm);

            card.classList.toggle('hidden', !isVisible);
            card.setAttribute('aria-hidden', String(!isVisible));
            visibleCount += Number(isVisible);
        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
            emptyState.classList.toggle('flex', visibleCount === 0);
        }

        if (resultCount) {
            resultCount.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'team member' : 'team members'}.`;
        }
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeFilter = button.dataset.teamFilter ?? 'all';

            filterButtons.forEach((filterButton) => {
                const isActive = filterButton === button;

                filterButton.setAttribute('aria-pressed', String(isActive));
                filterButton.classList.toggle('bg-white', isActive);
                filterButton.classList.toggle('text-slate-900', isActive);
                filterButton.classList.toggle('shadow-sm', isActive);
                filterButton.classList.toggle('text-slate-500', !isActive);
            });

            updateTeam();
        });
    });

    searchInput?.addEventListener('input', updateTeam);
    resetButton?.addEventListener('click', () => {
        if (searchInput instanceof HTMLInputElement) {
            searchInput.value = '';
        }

        const everyoneButton = filterButtons.find((button) => button.dataset.teamFilter === 'all');

        if (everyoneButton instanceof HTMLButtonElement) {
            everyoneButton.click();
        } else {
            updateTeam();
        }
    });

    updateTeam();
};

initializeTeam();
