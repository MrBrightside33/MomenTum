import { request } from './http';
import { withSkeleton } from './loading';
import * as navProgress from './nav-progress';

const shouldHandleLink = (event, anchor, currentLayout) => {
    if (event.defaultPrevented
        || event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
        || (anchor.target !== '' && anchor.target !== '_self')
        || anchor.relList.contains('external')
        || anchor.hasAttribute('download')
        || anchor.closest('[data-no-soft]')
        || currentLayout !== 'app') {
        return false;
    }

    const url = new URL(anchor.href, window.location.origin);

    return url.origin === window.location.origin
        && url.href !== window.location.href
        && !['/login', '/register', '/forgot-password', '/reset-password', '/logout'].some((path) => (
            url.pathname === path || url.pathname.startsWith(`${path}/`)
        ))
        && !(url.pathname === window.location.pathname && url.hash.length > 0)
        && !['mailto:', 'tel:'].includes(url.protocol);
};

const closeTransientUi = () => {
    while (document.querySelector('[data-modal]:not(.hidden)')) {
        const topModalId = window.Modal?.top();

        if (!topModalId || !window.Modal.close(topModalId)) {
            return false;
        }
    }

    const menuToggle = document.querySelector('[data-admin-menu-toggle]');

    if (menuToggle?.getAttribute('aria-expanded') === 'true') {
        menuToggle.click();
    }

    return true;
};

const replaceSoftRegion = (fetchedDocument, selector) => {
    const current = document.querySelector(`[data-soft-swap="${selector}"]`);
    const incoming = fetchedDocument.querySelector(`[data-soft-swap="${selector}"]`);

    if (!current || !incoming) {
        return;
    }

    current.replaceWith(incoming.cloneNode(true));
};

const initializeSoftNavigation = () => {
    const layout = document.querySelector('meta[name="layout"]')?.content;

    if (layout !== 'app') {
        return;
    }

    let navigationSequence = 0;

    const navigate = async (url, { push = true, skeleton = 'generic-page' } = {}) => {
        const main = document.getElementById('page-main');

        if (!main || !closeTransientUi()) {
            return;
        }

        const sequence = ++navigationSequence;
        const requestedUrl = new URL(url, window.location.origin);
        navProgress.start();
        let nextTitle = '';
        let nextTheme = '';
        let nextUrl = requestedUrl.href;
        let csrfToken = '';

        try {
            await withSkeleton(main, skeleton, async () => {
                const result = await request(url, {
                    headers: { Accept: 'text/html', 'X-Soft-Nav': '1' },
                    key: 'soft-navigation',
                    responseType: 'text',
                });
                const fetchedDocument = new DOMParser().parseFromString(result.data, 'text/html');
                const fetchedLayout = fetchedDocument.querySelector('meta[name="layout"]')?.content;
                const nextMain = fetchedDocument.getElementById('page-main');

                if (fetchedLayout !== layout || !nextMain) {
                    throw new Error('The requested page uses a different layout.');
                }

                nextTitle = fetchedDocument.title;
                nextTheme = fetchedDocument.documentElement.dataset.theme ?? '';
                csrfToken = fetchedDocument.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const resolvedUrl = new URL(result.url, window.location.origin);

                if (resolvedUrl.origin === window.location.origin) {
                    nextUrl = resolvedUrl.href;
                }

                replaceSoftRegion(fetchedDocument, 'app-sidebar-nav');
                replaceSoftRegion(fetchedDocument, 'inbox-link');
                replaceSoftRegion(fetchedDocument, 'admin-sidebar-nav');
                replaceSoftRegion(fetchedDocument, 'admin-search');
                replaceSoftRegion(fetchedDocument, 'admin-security-link');

                return nextMain;
            });

            if (sequence !== navigationSequence) {
                return;
            }

            const refreshedMain = document.getElementById('page-main');

            if (nextTitle) {
                document.title = nextTitle;
            }

            if (nextTheme) {
                document.documentElement.dataset.theme = nextTheme;
            }

            if (csrfToken) {
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrfToken);
            }

            if (push) {
                window.history.pushState({ softNavigation: true }, '', nextUrl);
            }

            window.scrollTo({ top: 0, behavior: 'auto' });
            const heading = refreshedMain?.querySelector('h1');

            if (heading) {
                heading.tabIndex = -1;
                heading.focus({ preventScroll: true });
            }

            const liveRegion = document.getElementById('live-region');
            liveRegion.textContent = nextTitle || 'Page loaded';
            document.dispatchEvent(new CustomEvent('page:load'));
            navProgress.done();
        } catch (error) {
            if (sequence !== navigationSequence) {
                return;
            }

            console.error('Soft navigation failed; loading the page normally.', error);
            navProgress.fail();
            window.location.assign(requestedUrl.href);
        }
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const anchor = event.target.closest('a[href]');

        if (!anchor || !shouldHandleLink(event, anchor, layout)) {
            return;
        }

        event.preventDefault();
        void navigate(anchor.href, {
            skeleton: anchor.dataset.skeleton || 'generic-page',
        });
    });

    window.addEventListener('popstate', () => {
        void navigate(window.location.href, { push: false });
    });
};

initializeSoftNavigation();
