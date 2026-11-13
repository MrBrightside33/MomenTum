import { showToast } from './toast';
import { request } from './http';

let activeUserDetailsController;

const initializeAdminNavigation = () => {
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const backdrop = document.querySelector('[data-admin-backdrop]');
    const toggle = document.querySelector('[data-admin-menu-toggle]');
    const desktopViewport = window.matchMedia('(min-width: 64rem)');

    if (!sidebar || !backdrop || !toggle) {
        return;
    }

    const closeMenu = () => {
        sidebar.classList.toggle('-translate-x-full', !desktopViewport.matches);
        sidebar.setAttribute('aria-hidden', String(!desktopViewport.matches));
        sidebar.inert = !desktopViewport.matches;
        backdrop.classList.add('hidden');
        backdrop.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation menu');
        document.body.classList.remove('overflow-hidden');
    };

    const openMenu = () => {
        sidebar.classList.remove('-translate-x-full');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebar.inert = false;
        backdrop.classList.remove('hidden');
        backdrop.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close navigation menu');
        document.body.classList.add('overflow-hidden');
    };

    toggle.addEventListener('click', () => {
        if (toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
        } else {
            openMenu();
        }
    });

    backdrop.addEventListener('click', closeMenu);
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            toggle.focus();
        }
    });

    desktopViewport.addEventListener('change', closeMenu);
    closeMenu();
};

initializeAdminNavigation();

const initializeAdminUserDetails = () => {
    activeUserDetailsController?.abort();
    activeUserDetailsController = new AbortController();
    const modal = document.getElementById('admin-user-details-modal');
    const status = modal?.querySelector('[data-user-details-status]');
    const content = modal?.querySelector('[data-user-details-content]');
    const title = modal?.querySelector('#admin-user-details-modal-title');

    if (!modal || !status || !content || !title) {
        return;
    }

    let activeRequest = 0;
    const fields = [
        'email',
        'role',
        'status',
        'job_title',
        'joined',
        'last_active',
        'assigned_tasks',
        'owned_projects',
        'bio',
    ];

    modal.addEventListener('modal:close', () => {
        activeRequest += 1;
    });

    document.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-user-details-url]');
        const detailsUrlValue = trigger?.getAttribute('data-user-details-url');

        if (!detailsUrlValue) {
            return;
        }

        const requestId = ++activeRequest;
        status.classList.remove('hidden');
        status.classList.remove('text-red-600');
        status.classList.add('text-slate-500');
        title.textContent = 'User details';
        status.textContent = 'Loading user details...';
        content.classList.add('hidden');

        try {
            const detailsUrl = new URL(detailsUrlValue, window.location.origin);

            if (detailsUrl.origin !== window.location.origin) {
                throw new Error('The user details URL is invalid.');
            }

            const { data: result } = await request(detailsUrl, {
                headers: { Accept: 'application/json' },
            });

            const data = result?.data;

            if (
                !data
                || fields.some((field) => !(field in data))
                || typeof data.name !== 'string'
            ) {
                throw new Error('The user details response was incomplete.');
            }

            fields.forEach((field) => {
                const element = modal.querySelector(`[data-user-detail="${field}"]`);

                if (!element) {
                    throw new Error(`The user details field "${field}" is unavailable.`);
                }

                const value = data[field];
                element.textContent = value === null || value === ''
                    ? 'Not provided'
                    : String(value);
            });

            if (requestId !== activeRequest) {
                return;
            }

            title.textContent = data.name;
            status.classList.add('hidden');
            content.classList.remove('hidden');
        } catch (error) {
            if (requestId !== activeRequest) {
                return;
            }

            status.classList.remove('text-slate-500');
            status.classList.add('text-red-600');
            status.textContent = 'Unable to load user details. Please try again.';
            showToast({
                type: 'error',
                title: 'Unable to load user details',
                message: 'Please try again.',
            });
            console.error('Unable to load admin user details.', error);
        }
    }, { signal: activeUserDetailsController.signal });
};

document.addEventListener('page:load', initializeAdminUserDetails);
initializeAdminUserDetails();
