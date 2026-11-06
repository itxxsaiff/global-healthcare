// "Install the app" prompt (client doc: automated Add to Home Screen prompt).
// Android/desktop Chrome: native prompt via beforeinstallprompt.
// iOS Safari has no API, so we show a one-time hint pointing at Share → Add to Home Screen.
const banner = document.getElementById('install-banner');
const DISMISS_KEY = 'pwa-dismissed-at';

const dismissedRecently = () => {
    try {
        return Date.now() - parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10) < 1000 * 60 * 60 * 24 * 14;
    } catch {
        return false;
    }
};
const remember = () => {
    try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch { /* private mode */ }
};
const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;

if (banner && !standalone && !dismissedRecently()) {
    let deferred = null;
    const show = (mode) => {
        banner.dataset.mode = mode;
        banner.hidden = false;
    };

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferred = e;
        setTimeout(() => show('native'), 8000); // after they've had a look around
    });

    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent) && !/crios|fxios/i.test(navigator.userAgent);
    if (isIos) setTimeout(() => show('ios'), 12000);

    banner.querySelector('[data-install]')?.addEventListener('click', async () => {
        if (deferred) {
            deferred.prompt();
            await deferred.userChoice;
            deferred = null;
        }
        banner.hidden = true;
        remember();
    });
    banner.querySelector('[data-dismiss]')?.addEventListener('click', () => {
        banner.hidden = true;
        remember();
    });
}
