import { request } from './http';

const initializeThemeToggle = () => {
    const form = document.querySelector('[data-theme-toggle-form]');
    const button = form?.querySelector('[data-theme-toggle]');
    const appearanceInput = form?.querySelector('[data-theme-appearance-input]');
    const status = document.querySelector('[data-theme-toggle-status]');

    if (!(form instanceof HTMLFormElement)
        || !(button instanceof HTMLButtonElement)
        || !(appearanceInput instanceof HTMLInputElement)
        || !status) {
        return;
    }

    const root = document.documentElement;
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
    const isAuthenticated = form.dataset.themeAuthenticated === 'true';
    const storageKey = 'momentum-appearance';

    if (!isAuthenticated) {
        try {
            const savedAppearance = window.localStorage.getItem(storageKey);

            if (savedAppearance === 'light' || savedAppearance === 'dark') {
                root.dataset.theme = savedAppearance;
            }
        } catch (error) {
            console.error('Unable to load the saved appearance preference.', error);
        }
    }

    const effectiveTheme = () => {
        if (root.dataset.theme === 'dark' || root.dataset.theme === 'light') {
            return root.dataset.theme;
        }

        return systemTheme.matches ? 'dark' : 'light';
    };

    const renderThemeControl = () => {
        const isDarkModeEnabled = effectiveTheme() === 'dark';

        button.setAttribute('aria-pressed', String(isDarkModeEnabled));
        appearanceInput.value = isDarkModeEnabled ? 'light' : 'dark';

        form.querySelectorAll('[data-theme-icon]').forEach((icon) => {
            icon.classList.toggle('hidden', icon.dataset.themeIcon !== (isDarkModeEnabled ? 'sun' : 'moon'));
        });
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (form.dataset.submitting === 'true') {
            return;
        }

        const nextAppearance = effectiveTheme() === 'dark' ? 'light' : 'dark';

        if (!isAuthenticated) {
            root.dataset.theme = nextAppearance;
            renderThemeControl();

            try {
                window.localStorage.setItem(storageKey, nextAppearance);
                status.textContent = `${nextAppearance === 'dark' ? 'Dark' : 'Light'} mode enabled.`;
            } catch (error) {
                status.textContent = 'Theme changed for this page, but could not be saved in this browser.';
                status.className = 'max-w-36 text-xs text-red-600';
                console.error('Unable to save the guest appearance preference.', error);
            }

            return;
        }

        const previousAppearance = root.dataset.theme ?? 'system';

        form.dataset.submitting = 'true';
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        status.textContent = '';
        status.className = 'sr-only';
        root.dataset.theme = nextAppearance;
        renderThemeControl();

        try {
            const { data: result } = await request(form.action, {
                method: 'PUT',
                json: { appearance: nextAppearance },
            });

            if (result.appearance !== nextAppearance) {
                throw new Error('The server returned an unexpected appearance preference.');
            }

            root.dataset.theme = result.appearance;
            status.textContent = `${result.appearance === 'dark' ? 'Dark' : 'Light'} mode enabled.`;
        } catch (error) {
            root.dataset.theme = previousAppearance;
            status.textContent = 'Unable to save the theme preference. Please try again.';
            status.classList.remove('sr-only');
            status.classList.add('max-w-36', 'text-xs', 'text-red-600');
            console.error('Unable to update the appearance preference.', error);
        } finally {
            delete form.dataset.submitting;
            button.disabled = false;
            button.removeAttribute('aria-busy');
            renderThemeControl();
        }
    });

    systemTheme.addEventListener('change', () => {
        if (root.dataset.theme === 'system') {
            renderThemeControl();
        }
    });

    renderThemeControl();
};

initializeThemeToggle();
