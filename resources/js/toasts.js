const types = {
    success: { title: 'Sucesso', icon: 'check-circle-2', duration: 6000 },
    error: { title: 'Não foi possível concluir', icon: 'circle-alert', duration: 0 },
    warning: { title: 'Atenção', icon: 'triangle-alert', duration: 0 },
    info: { title: 'Informação', icon: 'info', duration: 6000 },
};

let region;
let template;
let renderIcons = () => {};
let announcementTimer;
const entries = new Map();
const announcements = { polite: [], assertive: [] };
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function announce(type, title, message) {
    const priority = ['error', 'warning'].includes(type) ? 'assertive' : 'polite';
    announcements[priority].push(`${title}. ${message}`);
    document.querySelector(`[data-toast-live="${priority}"]`).textContent = '';
    window.clearTimeout(announcementTimer);
    announcementTimer = window.setTimeout(() => {
        Object.entries(announcements).forEach(([level, messages]) => {
            if (messages.length) {
                document.querySelector(`[data-toast-live="${level}"]`).textContent = messages.join(' ');
                messages.length = 0;
            }
        });
    }, 100);
}

function restoreFocus(entry) {
    const next = [...entries.keys()].find(toast => toast !== entry.toast);
    const target = next?.querySelector('[data-toast-dismiss]') ?? entry.returnFocus;
    if (target instanceof HTMLElement && target.isConnected && target !== document.body && !target.matches(':disabled')) {
        target.focus({ preventScroll: true });
        return;
    }

    const main = document.querySelector('main');
    if (main) {
        if (!main.hasAttribute('tabindex')) {
            main.setAttribute('tabindex', '-1');
            main.addEventListener('blur', () => main.removeAttribute('tabindex'), { once: true });
        }
        main.focus({ preventScroll: true });
    }
}

function dismiss(entry) {
    if (!entries.has(entry.toast)) return;
    const hadFocus = entry.toast.contains(document.activeElement);
    entries.delete(entry.toast);
    window.clearTimeout(entry.timer);
    entry.animation?.cancel();
    if (hadFocus) restoreFocus(entry);
    entry.toast.inert = true;

    if (reducedMotion()) {
        entry.toast.remove();
    } else {
        entry.toast.classList.add('is-leaving');
        window.setTimeout(() => entry.toast.remove(), 180);
    }
}

function pause(entry) {
    if (entry.timer !== null) {
        window.clearTimeout(entry.timer);
        entry.remaining = Math.max(0, entry.remaining - (performance.now() - entry.startedAt));
        entry.timer = null;
    }
    entry.animation?.pause();
}

function resume(entry) {
    if (!entries.has(entry.toast) || !entry.duration || entry.timer !== null || document.hidden || entry.toast.matches(':hover, :focus-within')) return;
    entry.startedAt = performance.now();
    entry.animation?.play();
    entry.timer = window.setTimeout(() => dismiss(entry), entry.remaining);
}

function startTimer(entry) {
    pause(entry);
    entry.animation?.cancel();
    entry.remaining = entry.duration;
    const progress = entry.toast.querySelector('[data-toast-progress]');
    progress.hidden = !entry.duration || reducedMotion();
    if (!progress.hidden) {
        entry.animation = progress.animate([{ transform: 'scaleX(1)' }, { transform: 'scaleX(0)' }], {
            duration: entry.duration,
            fill: 'forwards',
        });
        entry.animation.pause();
    }
    resume(entry);
}

function register(toast, { duration, announceMessage = true } = {}) {
    const type = Object.hasOwn(types, toast.dataset.toastType) ? toast.dataset.toastType : 'info';
    const title = toast.querySelector('[data-toast-title]').textContent.trim();
    const message = toast.querySelector('[data-toast-message]').textContent.trim();
    const key = JSON.stringify([type, title, message]);
    const duplicate = [...entries.values()].find(entry => entry.key === key);
    if (duplicate) {
        toast.remove();
        startTimer(duplicate);
        if (announceMessage) announce(type, title, message);
        return duplicate.toast;
    }

    const entry = {
        toast, key, timer: null, animation: null, startedAt: 0, remaining: 0,
        duration: Number.isFinite(duration) && duration >= 0 ? duration : types[type].duration,
        returnFocus: document.activeElement,
    };
    entries.set(toast, entry);
    toast.querySelector('[data-toast-dismiss]').hidden = false;
    toast.querySelector('[data-toast-dismiss]').addEventListener('click', () => dismiss(entry));
    toast.addEventListener('pointerenter', () => pause(entry));
    toast.addEventListener('pointerleave', () => resume(entry));
    toast.addEventListener('focusin', () => pause(entry));
    toast.addEventListener('focusout', () => queueMicrotask(() => {
        if (entries.has(toast)) resume(entry);
    }));
    toast.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            dismiss(entry);
        }
    });
    startTimer(entry);
    if (announceMessage) announce(type, title, message);
    return toast;
}

// Também pode ser usado por scripts nativos via CustomEvent('app:notify', { detail: { type, message } }).
export function notify({ type = 'info', title, message, duration } = {}) {
    if (!region || !template || typeof message !== 'string' || !message.trim()) return null;
    type = Object.hasOwn(types, type) ? type : 'info';
    title = typeof title === 'string' && title.trim() ? title.trim() : types[type].title;
    const toast = template.content.firstElementChild.cloneNode(true);
    toast.dataset.toastType = type;
    toast.setAttribute('aria-label', title);
    toast.querySelector('[data-toast-title]').textContent = title;
    toast.querySelector('[data-toast-message]').textContent = message.trim();
    toast.querySelector('[data-toast-icon] i').dataset.lucide = types[type].icon;
    region.append(toast);
    renderIcons(toast);
    return register(toast, { duration });
}

export function initToasts({ renderIcons: refreshIcons } = {}) {
    if (region) return;
    region = document.querySelector('[data-toast-region]');
    template = document.querySelector('[data-toast-template]');
    if (!region || !template) return;
    if (typeof refreshIcons === 'function') renderIcons = refreshIcons;
    region.dataset.toastReady = '';
    region.querySelectorAll('[data-toast]').forEach(toast => register(toast));
    document.querySelectorAll('[data-toast-source]').forEach(source => {
        const message = [...source.querySelectorAll('[data-toast-source-message]')]
            .map(item => item.textContent.trim()).filter(Boolean).join('\n');
        if (notify({ type: source.dataset.toastType, title: source.dataset.toastTitle, message })) {
            source.hidden = true;
        }
    });

    document.addEventListener('visibilitychange', () => entries.forEach(entry => document.hidden ? pause(entry) : resume(entry)));
    window.addEventListener('pagehide', () => entries.forEach(pause));
    window.addEventListener('pageshow', () => entries.forEach(resume));
    document.addEventListener('app:notify', event => {
        if (event.detail && typeof event.detail === 'object') notify(event.detail);
    });
}
