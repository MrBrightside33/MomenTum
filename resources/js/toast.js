const iconPaths = {
    success: 'M20 6 9 17l-5-5',
    error: 'M18 6 6 18M6 6l12 12',
    info: 'M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z',
};

const toastStyles = {
    success: 'bg-emerald-50 text-emerald-600',
    error: 'bg-red-50 text-red-600',
    info: 'bg-indigo-50 text-indigo-600',
};

export const showToast = (typeOrOptions, message, detail = '') => {
    const options = typeof typeOrOptions === 'string'
        ? { type: typeOrOptions, title: message, message: detail }
        : typeOrOptions;
    const type = Object.hasOwn(iconPaths, options?.type) ? options.type : 'info';
    const title = options?.title ?? options?.message ?? '';
    const description = options?.title ? options.message : '';
    const duration = Number.isFinite(options?.duration) ? options.duration : 4000;
    let container = document.getElementById('toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'pointer-events-none fixed bottom-6 right-4 z-[60] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-3 sm:right-6';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'false');
        document.body.append(container);
    }

    const toast = document.createElement('div');
    const iconCircle = document.createElement('span');
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    const textContainer = document.createElement('div');
    const titleElement = document.createElement('p');

    toast.className = 'pointer-events-auto flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-xl transition-opacity duration-200';
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    iconCircle.className = `flex size-6 shrink-0 items-center justify-center rounded-full ${toastStyles[type]}`;
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('stroke', 'currentColor');
    icon.setAttribute('stroke-width', '2');
    icon.setAttribute('stroke-linecap', 'round');
    icon.setAttribute('stroke-linejoin', 'round');
    icon.classList.add('size-4');
    path.setAttribute('d', iconPaths[type]);
    icon.append(path);
    iconCircle.append(icon);
    textContainer.className = 'min-w-0';
    titleElement.className = 'font-semibold';
    titleElement.textContent = title;
    textContainer.append(titleElement);

    if (description) {
        const descriptionElement = document.createElement('p');
        descriptionElement.className = 'text-xs text-slate-500';
        descriptionElement.textContent = description;
        textContainer.append(descriptionElement);
    }

    toast.append(iconCircle, textContainer);
    container.append(toast);

    window.setTimeout(() => {
        toast.classList.add('opacity-0');
        window.setTimeout(() => toast.remove(), 200);
    }, duration);
};

window.showToast = showToast;
