import { MIN_VISIBLE, SHOW_DELAY } from './loading-config';

const targetTokens = new WeakMap();
const buttonStates = new WeakMap();
const rowStateClasses = ['row-saving', 'row-deleting', 'row-ghost', 'row-collapse', 'flash-success'];

const announce = (message) => {
    const liveRegion = document.getElementById('live-region');

    if (liveRegion) {
        liveRegion.textContent = message;
    }
};

const getSkeletonCount = (target) => {
    const key = `mt-skeleton-count:${target.id || 'region'}`;

    try {
        const stored = Number.parseInt(window.sessionStorage.getItem(key) ?? '', 10);

        return Number.isInteger(stored) ? Math.max(1, Math.min(10, stored)) : 5;
    } catch (error) {
        console.error('Could not read the previous skeleton row count.', error);

        return 5;
    }
};

const rememberSkeletonCount = (target) => {
    const count = target.querySelectorAll(
        '[data-task-row], [data-project-card], [data-inbox-item], tbody tr',
    ).length;

    if (!target.id || count === 0) {
        return;
    }

    try {
        window.sessionStorage.setItem(`mt-skeleton-count:${target.id}`, String(Math.min(10, count)));
    } catch (error) {
        console.error('Could not save the previous skeleton row count.', error);
    }
};

const cloneTemplate = (name, count) => {
    const template = document.getElementById(`sk-${name}`);

    if (!(template instanceof HTMLTemplateElement)) {
        throw new Error(`The skeleton template "sk-${name}" is not available.`);
    }

    const fragment = template.content.cloneNode(true);
    const rows = Array.from(fragment.querySelectorAll('[data-skeleton-row]'));

    rows.slice(count).forEach((row) => row.remove());

    return fragment;
};

const makeErrorState = (message, offline, retry) => {
    const region = document.createElement('div');
    const title = document.createElement('p');
    const description = document.createElement('p');
    const button = document.createElement('button');

    region.className = 'flex min-h-40 flex-col items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white p-6 text-center';
    region.setAttribute('role', 'alert');
    title.className = 'font-semibold text-slate-900';
    title.textContent = "Couldn't load this section";
    description.className = 'text-sm text-slate-500';
    description.textContent = offline ? "You're offline. Check your connection and try again." : message;
    button.type = 'button';
    button.className = 'min-h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white';
    button.textContent = 'Retry';
    button.addEventListener('click', retry, { once: true });
    region.append(title, description, button);

    return region;
};

export const withSkeleton = async (
    target,
    name,
    task,
    { delay = SHOW_DELAY, minVisible = MIN_VISIBLE, onError } = {},
) => {
    if (!(target instanceof HTMLElement) || typeof task !== 'function') {
        throw new TypeError('withSkeleton requires a target element and a task function.');
    }

    const token = (targetTokens.get(target) ?? 0) + 1;
    targetTokens.set(target, token);
    const originalMinHeight = target.style.minHeight;
    const originalChildren = Array.from(target.childNodes, (child) => child.cloneNode(true));
    const previousHeight = target.getBoundingClientRect().height;
    const startedAt = performance.now();
    let skeletonShownAt = null;
    let delayTimer;

    if (previousHeight > 0) {
        target.style.minHeight = `${previousHeight}px`;
    }

    const show = () => {
        if (targetTokens.get(target) !== token) {
            return;
        }

        target.replaceChildren(cloneTemplate(name, getSkeletonCount(target)));
        target.setAttribute('aria-busy', 'true');
        skeletonShownAt = performance.now();
        announce('Loading');
    };

    delayTimer = window.setTimeout(show, delay);

    try {
        const result = await task();

        if (targetTokens.get(target) !== token) {
            return result;
        }

        window.clearTimeout(delayTimer);

        if (skeletonShownAt !== null) {
            const visibleFor = performance.now() - skeletonShownAt;
            const remaining = Math.max(0, minVisible - visibleFor);

            if (remaining > 0) {
                await new Promise((resolve) => window.setTimeout(resolve, remaining));
            }
        } else if (performance.now() - startedAt >= delay) {
            show();
            await new Promise((resolve) => window.setTimeout(resolve, minVisible));
        }

        if (targetTokens.get(target) !== token) {
            return result;
        }

        let renderedTarget = target;

        if (result instanceof HTMLElement && result.id && result.id === target.id) {
            target.replaceWith(result);
            renderedTarget = result;
        } else if (result instanceof Node) {
            target.replaceChildren(result);
        } else if (result !== undefined) {
            target.textContent = String(result);
        }

        renderedTarget.removeAttribute('aria-busy');
        renderedTarget.style.minHeight = originalMinHeight;
        rememberSkeletonCount(renderedTarget);
        announce('Loaded');

        return result;
    } catch (error) {
        window.clearTimeout(delayTimer);

        if (targetTokens.get(target) !== token) {
            throw error;
        }

        if (skeletonShownAt !== null) {
            const remaining = Math.max(0, minVisible - (performance.now() - skeletonShownAt));

            if (remaining > 0) {
                await new Promise((resolve) => window.setTimeout(resolve, remaining));
            }
        }

        if (targetTokens.get(target) === token) {
            if (error?.code === 'aborted') {
                target.replaceChildren(...originalChildren);
                target.removeAttribute('aria-busy');
                target.style.minHeight = originalMinHeight;

                throw error;
            }

            const offline = navigator.onLine === false;
            const message = error instanceof Error ? error.message : 'Please try again.';
            const retry = () => {
                void withSkeleton(target, name, task, { delay, minVisible, onError });
            };

            target.replaceChildren(makeErrorState(message, offline, retry));
            target.removeAttribute('aria-busy');
            target.style.minHeight = originalMinHeight;
            announce(offline ? "You're offline" : "Couldn't load this section");
            onError?.(error);
        }

        throw error;
    }
};

const makeLoader = (loader) => {
    const element = document.createElement('span');

    if (loader === 'dots') {
        element.className = 'loader-dots';
        element.setAttribute('aria-hidden', 'true');
        for (let dot = 0; dot < 3; dot++) {
            element.append(document.createElement('span'));
        }

        return element;
    }

    element.className = loader === 'ring-red' ? 'loader-ring-red' : 'loader-ring';
    element.setAttribute('aria-hidden', 'true');

    return element;
};

const restoreButton = (button, state) => {
    const saved = buttonStates.get(button);

    if (!saved) {
        return;
    }

    button.classList.remove('btn-loading', 'mt-shake');
    button.removeEventListener('click', saved.blockInteraction, true);
    button.removeEventListener('keydown', saved.blockInteraction, true);

    if (saved.loadingWidth) {
        button.style.setProperty('--btn-loading-width', saved.loadingWidth, saved.loadingWidthPriority);
    } else {
        button.style.removeProperty('--btn-loading-width');
    }

    const restoreAttribute = (name, value) => {
        if (value === null) {
            button.removeAttribute(name);
        } else {
            button.setAttribute(name, value);
        }
    };

    restoreAttribute('aria-busy', saved.ariaBusy);
    restoreAttribute('aria-disabled', saved.ariaDisabled);
    restoreAttribute('aria-label', saved.ariaLabel);

    if (state === 'success') {
        const label = saved.successLabel;
        const result = document.createElement('span');
        result.className = 'inline-flex items-center gap-2';
        result.setAttribute('aria-live', 'polite');
        result.textContent = `✓ ${label}`;
        button.replaceChildren(result);
        saved.restoreTimer = window.setTimeout(() => {
            if (button.isConnected) {
                restoreButton(button, 'idle');
            }
        }, 600);
    } else if (state === 'error') {
        button.replaceChildren(...saved.children.map((child) => child.cloneNode(true)));
        button.classList.add('mt-shake');
        saved.restoreTimer = window.setTimeout(() => {
            if (button.isConnected) {
                restoreButton(button, 'idle');
            }
        }, 350);
    } else {
        window.clearTimeout(saved.restoreTimer);
        button.replaceChildren(...saved.children.map((child) => child.cloneNode(true)));
        buttonStates.delete(button);
    }
};

export const setButtonLoading = (button, { label, loader = 'ring' }) => {
    if (!(button instanceof HTMLButtonElement)) {
        throw new TypeError('setButtonLoading requires a button element.');
    }

    if (buttonStates.has(button)) {
        return (state = 'idle') => restoreButton(button, state);
    }

    const blockInteraction = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };
    const saved = {
        children: Array.from(button.childNodes, (child) => child.cloneNode(true)),
        blockInteraction,
        successLabel: button.dataset.successLabel ?? 'Done',
        ariaBusy: button.getAttribute('aria-busy'),
        ariaDisabled: button.getAttribute('aria-disabled'),
        ariaLabel: button.getAttribute('aria-label'),
        loadingWidth: button.style.getPropertyValue('--btn-loading-width'),
        loadingWidthPriority: button.style.getPropertyPriority('--btn-loading-width'),
        restoreTimer: null,
    };

    buttonStates.set(button, saved);
    button.style.setProperty('--btn-loading-width', `${button.getBoundingClientRect().width}px`);
    button.classList.add('btn-loading');
    button.setAttribute('aria-busy', 'true');
    button.setAttribute('aria-disabled', 'true');
    button.setAttribute('aria-label', label);
    button.addEventListener('click', blockInteraction, true);
    button.addEventListener('keydown', blockInteraction, true);

    const content = document.createElement('span');
    const text = document.createElement('span');
    content.className = 'inline-flex items-center justify-center gap-2';
    text.textContent = label;
    content.append(makeLoader(loader), text);
    button.replaceChildren(content);

    return (state = 'idle') => restoreButton(button, state);
};

export const setRowState = (row, state) => {
    if (!(row instanceof HTMLElement)) {
        throw new TypeError('setRowState requires a row element.');
    }

    row.classList.remove(...rowStateClasses);
    row.querySelector('[data-row-loading-label]')?.remove();

    const labels = {
        saving: 'Saving...',
        deleting: 'Deleting...',
        ghost: 'Creating...',
        success: 'Saved',
    };

    if (state === 'idle') {
        row.removeAttribute('aria-busy');
        return;
    }

    if (!Object.hasOwn(labels, state)) {
        throw new TypeError(`Unsupported row loading state: ${state}`);
    }

    const className = state === 'success' ? 'flash-success' : `row-${state}`;
    row.classList.add(className);
    row.setAttribute('aria-busy', String(state !== 'success'));

    const status = document.createElement('span');
    status.dataset.rowLoadingLabel = '';
    status.className = 'ml-2 text-xs font-semibold text-slate-500';
    status.setAttribute('aria-live', 'polite');
    status.textContent = labels[state];
    row.append(status);

    if (state === 'success') {
        window.setTimeout(() => {
            if (row.isConnected) {
                setRowState(row, 'idle');
            }
        }, 600);
    }
};

export const setInlineLoading = (element) => {
    if (!(element instanceof HTMLElement)) {
        throw new TypeError('setInlineLoading requires an element.');
    }

    const originalChildren = Array.from(element.childNodes, (child) => child.cloneNode(true));
    const width = Math.max(24, element.getBoundingClientRect().width);
    const placeholder = document.createElement('span');

    placeholder.className = 'sk inline-block h-4 align-middle';
    placeholder.style.width = `${width}px`;
    placeholder.setAttribute('aria-hidden', 'true');
    element.replaceChildren(placeholder);
    element.setAttribute('aria-busy', 'true');

    return (value) => {
        if (!element.isConnected) {
            return;
        }

        if (value instanceof Node) {
            element.replaceChildren(value);
        } else if (value !== undefined) {
            element.textContent = String(value);
        } else {
            element.replaceChildren(...originalChildren.map((child) => child.cloneNode(true)));
        }

        element.removeAttribute('aria-busy');
    };
};
