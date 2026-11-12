// Track modal order so keyboard behavior always applies to the top-most dialog.
const modalStack = [];
const previousFocus = new Map();

const getModal = (id) => document.getElementById(id);

const getTopModal = () => modalStack[modalStack.length - 1] ?? null;

const getFocusableElements = (panel) => Array.from(
    panel.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    ),
).filter((element) => !element.closest('[hidden]') && element.getClientRects().length > 0);

const open = (id) => {
    const overlay = getModal(id);

    if (!overlay || !overlay.matches('[data-modal]')) {
        return false;
    }

    if (!overlay.classList.contains('hidden')) {
        return true;
    }

    const panel = overlay.querySelector('[data-modal-panel]');
    if (!panel) {
        return false;
    }

    previousFocus.set(id, document.activeElement instanceof HTMLElement ? document.activeElement : null);
    overlay.classList.remove('hidden');
    overlay.setAttribute('aria-hidden', 'false');
    modalStack.push(overlay);
    document.documentElement.classList.add('overflow-hidden');

    const autofocus = overlay.querySelector('[data-autofocus]');
    const focusTarget = autofocus ?? getFocusableElements(panel)[0] ?? panel;
    focusTarget.focus();
    overlay.dispatchEvent(new CustomEvent('modal:open', { bubbles: true }));

    return true;
};

const close = (id) => {
    const overlay = getModal(id);

    if (!overlay || overlay.classList.contains('hidden') || getTopModal() !== overlay) {
        return false;
    }

    const mayClose = overlay.dispatchEvent(new CustomEvent('modal:beforeclose', {
        bubbles: true,
        cancelable: true,
    }));

    if (!mayClose) {
        return false;
    }

    overlay.classList.add('hidden');
    overlay.setAttribute('aria-hidden', 'true');
    modalStack.pop();

    if (modalStack.length === 0) {
        document.documentElement.classList.remove('overflow-hidden');
    }

    const opener = previousFocus.get(id);
    previousFocus.delete(id);

    if (opener?.isConnected) {
        opener.focus();
    } else {
        getTopModal()?.querySelector('[data-modal-panel]')?.focus();
    }

    overlay.dispatchEvent(new CustomEvent('modal:close', { bubbles: true }));

    return true;
};

// Delegate triggers and close actions so future modal contents need no extra wiring.
document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const target = event.target;
    const opener = target.closest('[data-modal-open]');

    if (opener) {
        event.preventDefault();
        open(opener.getAttribute('data-modal-open'));

        return;
    }

    const closer = target.closest('[data-modal-close]');

    if (closer) {
        const overlay = closer.closest('[data-modal]');

        if (overlay) {
            close(overlay.id);
        }

        return;
    }

    const overlay = target.closest('[data-modal]');

    if (overlay && target === overlay) {
        close(overlay.id);
    }
});

// Trap Tab navigation and close only the top-most modal with Escape.
document.addEventListener('keydown', (event) => {
    const overlay = getTopModal();

    if (!overlay) {
        if (
            event.key.toLowerCase() === 'n'
            && !event.altKey
            && !event.ctrlKey
            && !event.metaKey
            && !event.shiftKey
            && !(event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable="true"]'))
        ) {
            open('create-task-modal');
        }

        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        close(overlay.id);

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const panel = overlay.querySelector('[data-modal-panel]');
    const focusableElements = getFocusableElements(panel);

    if (focusableElements.length === 0) {
        event.preventDefault();
        panel.focus();

        return;
    }

    const first = focusableElements[0];
    const last = focusableElements[focusableElements.length - 1];

    if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) {
        event.preventDefault();
        first.focus();
    }
});

window.Modal = { open, close };
