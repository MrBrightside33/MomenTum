const bar = document.getElementById('nav-progress');
const fill = bar?.firstElementChild;
let progress = 0;
let tick;
let finishTimer;

const render = () => {
    if (fill instanceof HTMLElement) {
        fill.style.transform = `scaleX(${progress})`;
    }
};

export const start = () => {
    if (!bar || !fill) {
        return;
    }

    window.clearInterval(tick);
    window.clearTimeout(finishTimer);
    progress = 0.08;
    bar.classList.remove('hidden', 'bg-red-600');
    bar.classList.add('bg-indigo-600');
    fill.classList.remove('bg-red-500');
    fill.classList.add('bg-indigo-300');
    fill.classList.add('transition-transform', 'duration-300', 'ease-out');
    render();
    tick = window.setInterval(() => {
        progress = Math.min(0.82, progress + ((0.82 - progress) * 0.12));
        render();
    }, 180);
};

const finish = (failed) => {
    if (!bar || !fill) {
        return;
    }

    window.clearInterval(tick);
    progress = 1;
    bar.classList.toggle('bg-red-600', failed);
    bar.classList.toggle('bg-indigo-600', !failed);
    fill.classList.toggle('bg-red-500', failed);
    fill.classList.toggle('bg-indigo-300', !failed);
    render();
    finishTimer = window.setTimeout(() => {
        bar.classList.add('hidden');
        progress = 0;
        render();
    }, 220);
};

export const done = () => finish(false);
export const fail = () => finish(true);
