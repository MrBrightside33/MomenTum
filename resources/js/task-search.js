import { request } from './http';

const initializeTaskSearch = () => {
    const form = document.querySelector('[data-task-search-form]');
    const input = form?.querySelector('#header-task-search');
    const suggestionsList = form?.querySelector('#task-search-suggestions');
    const status = form?.querySelector('#task-search-status');

    if (!(form instanceof HTMLFormElement)
        || !(input instanceof HTMLInputElement)
        || !(suggestionsList instanceof HTMLUListElement)
        || !status) {
        return;
    }

    let debounceTimer = null;
    let requestController = null;
    let suggestions = [];
    let activeIndex = -1;

    const closeSuggestions = () => {
        window.clearTimeout(debounceTimer);
        requestController?.abort();
        suggestionsList.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        suggestionsList.replaceChildren();
        suggestions = [];
        activeIndex = -1;
        status.textContent = '';
    };

    const showMessage = (message) => {
        const item = document.createElement('li');

        item.className = 'px-4 py-3 text-sm text-slate-500';
        item.setAttribute('role', 'presentation');
        item.textContent = message;
        suggestionsList.replaceChildren(item);
        suggestionsList.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        input.removeAttribute('aria-activedescendant');
        status.textContent = message;
    };

    const setActiveOption = (index) => {
        activeIndex = index;

        suggestionsList.querySelectorAll('[role="option"]').forEach((option, optionIndex) => {
            const isActive = optionIndex === activeIndex;

            option.setAttribute('aria-selected', String(isActive));
            option.classList.toggle('bg-slate-50', isActive);
        });

        if (activeIndex >= 0) {
            input.setAttribute('aria-activedescendant', `task-search-option-${activeIndex}`);
        } else {
            input.removeAttribute('aria-activedescendant');
        }
    };

    const chooseSuggestion = (suggestion) => {
        input.value = suggestion.title;
        closeSuggestions();
        form.requestSubmit();
    };

    const renderSuggestions = (results) => {
        suggestions = results;
        activeIndex = -1;
        suggestionsList.replaceChildren();

        if (suggestions.length === 0) {
            showMessage('No matching tasks found.');

            return;
        }

        suggestions.forEach((suggestion, index) => {
            const option = document.createElement('li');
            const title = document.createElement('span');
            const project = document.createElement('span');

            option.id = `task-search-option-${index}`;
            option.className = 'flex cursor-pointer flex-col gap-0.5 px-4 py-3 text-sm transition hover:bg-slate-50';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.dataset.taskSuggestionIndex = String(index);

            title.className = 'font-medium text-slate-900';
            title.textContent = suggestion.title;
            project.className = 'text-xs text-slate-500';
            project.textContent = suggestion.project;
            option.append(title, project);
            suggestionsList.append(option);
        });

        suggestionsList.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        status.textContent = `${suggestions.length} task suggestions available.`;
    };

    const fetchSuggestions = async (query) => {
        const suggestionsUrl = form.dataset.taskSearchSuggestionsUrl;

        if (!suggestionsUrl) {
            console.error('The task search suggestions endpoint is missing.');
            showMessage('Suggestions are unavailable. Press Enter to search.');

            return;
        }

        requestController?.abort();
        requestController = new AbortController();
        const requestId = requestController;
        const url = new URL(suggestionsUrl, window.location.origin);
        url.searchParams.set('q', query);

        try {
            const { data: payload } = await request(url, {
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
                key: 'task-search-suggestions',
            });

            if (
                requestController !== requestId
                || input.value.trim() !== query
            ) {
                return;
            }

            if (
                !Array.isArray(payload.data)
                || payload.data.some((suggestion) => (
                    typeof suggestion?.id !== 'string'
                    || typeof suggestion.title !== 'string'
                    || typeof suggestion.project !== 'string'
                ))
            ) {
                throw new Error('The task suggestions response has an invalid format.');
            }

            renderSuggestions(payload.data);
        } catch (error) {
            if (
                error.code === 'aborted'
                || requestController !== requestId
                || input.value.trim() !== query
            ) {
                return;
            }

            console.error('Task search suggestions failed.', error);
            showMessage('Suggestions are unavailable. Press Enter to search.');
        }
    };

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        requestController?.abort();
        closeSuggestions();

        const query = input.value.trim();

        if (query.length < 2) {
            status.textContent = '';

            return;
        }

        showMessage('Searching for matching tasks...');
        debounceTimer = window.setTimeout(() => fetchSuggestions(query), 180);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !suggestionsList.hidden) {
            event.preventDefault();
            requestController?.abort();
            closeSuggestions();
            status.textContent = 'Task suggestions closed.';

            return;
        }

        if (suggestions.length === 0 || suggestionsList.hidden) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveOption((activeIndex + 1) % suggestions.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveOption(activeIndex <= 0 ? suggestions.length - 1 : activeIndex - 1);
        } else if (event.key === 'Enter' && activeIndex >= 0) {
            event.preventDefault();
            chooseSuggestion(suggestions[activeIndex]);
        }
    });

    suggestionsList.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const option = event.target.closest('[data-task-suggestion-index]');
        const suggestionIndex = Number(option?.dataset.taskSuggestionIndex);
        const suggestion = suggestions[suggestionIndex];

        if (suggestion) {
            chooseSuggestion(suggestion);
        }
    });

    suggestionsList.addEventListener('pointerdown', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-task-suggestion-index]')) {
            event.preventDefault();
        }
    });

    document.addEventListener('pointerdown', (event) => {
        if (event.target instanceof Node && !form.contains(event.target)) {
            requestController?.abort();
            closeSuggestions();
        }
    });

    form.addEventListener('submit', closeSuggestions);
};

initializeTaskSearch();
