// ============================================================
// Service Worker Registration — PASTE THIS AT THE END OF assets/app.js
// (it's what actually turns on offline mode; sw.js alone does nothing
// until something calls .register() on it)
// ============================================================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('Service worker registered, scope:', reg.scope))
            .catch(err => console.warn('Service worker registration failed:', err));
    });
}
