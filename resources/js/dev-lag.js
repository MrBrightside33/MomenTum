const initializeLagSimulator = () => {
    if (document.querySelector('meta[name="app-env"]')?.content !== 'local') {
        return;
    }

    const values = [0, 800, 2000, 4000];
    let activeValue = 0;
    const badge = document.createElement('p');

    badge.className = 'fixed bottom-4 left-4 z-[80] rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-800 shadow-lg';
    badge.hidden = true;
    badge.setAttribute('role', 'status');
    badge.setAttribute('aria-live', 'polite');
    document.body.append(badge);

    try {
        const storedValue = Number(window.localStorage.getItem('mt_lag') ?? 0);
        activeValue = values.includes(storedValue) ? storedValue : 0;
    } catch (error) {
        console.error('Could not read the development latency setting.', error);
    }

    const save = () => {
        badge.hidden = activeValue === 0;
        badge.textContent = `Lag ${activeValue} ms · Ctrl+Shift+L`;

        try {
            window.localStorage.setItem('mt_lag', String(activeValue));
        } catch (error) {
            console.error('Could not save the development latency setting.', error);
        }

        document.cookie = `mt_lag=${activeValue}; path=/; SameSite=Lax`;
    };

    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey && event.shiftKey && event.key.toLowerCase() === 'l') {
            event.preventDefault();
            const currentIndex = values.indexOf(activeValue);
            activeValue = values[(currentIndex + 1) % values.length];
            save();
        }
    });

    save();
};

initializeLagSimulator();
