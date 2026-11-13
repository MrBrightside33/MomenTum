import { request } from './http';
import { withSkeleton } from './loading';

const activeControllers = new Set();

const loadLazyRegion = async (target, signal) => {
    const regionUrl = target.dataset.lazyRegion;

    if (!regionUrl || !target.id) {
        throw new Error('A lazy region is missing its URL or identifier.');
    }

    const url = new URL(regionUrl, window.location.origin);

    if (url.origin !== window.location.origin) {
        throw new Error('The lazy region URL must be same-origin.');
    }

    const { data: responseText } = await request(url, {
        headers: { Accept: 'text/html' },
        signal,
        responseType: 'text',
        key: `lazy-region:${target.id}`,
    });
    const responseDocument = new DOMParser().parseFromString(responseText, 'text/html');
    const updatedRegion = responseDocument.getElementById(target.id);

    if (!(updatedRegion instanceof HTMLElement)) {
        throw new Error(`The lazy response is missing #${target.id}.`);
    }

    return updatedRegion;
};

export const initializeLazyRegions = () => {
    activeControllers.forEach((controller) => controller.abort());
    activeControllers.clear();

    document.querySelectorAll('[data-lazy-region]').forEach((target) => {
        if (!(target instanceof HTMLElement) || target.dataset.lazyStarted === 'true') {
            return;
        }

        const skeleton = target.dataset.lazySkeleton;

        if (!skeleton) {
            console.error('A lazy region is missing its skeleton template.', target);
            target.removeAttribute('aria-busy');
            target.textContent = "This section couldn't be loaded.";
            return;
        }

        target.dataset.lazyStarted = 'true';
        const controller = new AbortController();
        activeControllers.add(controller);

        void withSkeleton(target, skeleton, () => loadLazyRegion(target, controller.signal))
            .catch((error) => {
                if (error?.code !== 'aborted') {
                    console.error('Unable to load a lazy region.', error);
                }
            })
            .finally(() => activeControllers.delete(controller));
    });
};

document.addEventListener('page:load', initializeLazyRegions);
initializeLazyRegions();
