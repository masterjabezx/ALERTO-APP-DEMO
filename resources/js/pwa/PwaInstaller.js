/**
 * ALERTO APP — PWA Installation Experience & Service Worker Registration
 * Handles 'beforeinstallprompt', standalone display mode detection, iOS Safari instructions.
 */

class PwaInstaller {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isIos = false;
        this.isStandalone = false;
    }

    init() {
        // Detect Standalone Display Mode
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true ||
            document.referrer.includes('android-app://');

        this.isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

        if (this.isStandalone) {
            this.isInstalled = true;
            document.documentElement.classList.add('alerto-pwa-standalone');
        }

        // Native Chrome / Edge install prompt listener
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.isInstalled = false;

            // Dispatch event to show custom UI install button if not dismissed
            const dismissed = localStorage.getItem('alerto_pwa_install_dismissed');
            if (!dismissed) {
                window.dispatchEvent(new CustomEvent('alerto:can-install', {
                    detail: { promptAvailable: true, isIos: false }
                }));
            }
        });

        // App installed listener
        window.addEventListener('appinstalled', () => {
            this.deferredPrompt = null;
            this.isInstalled = true;
            localStorage.setItem('alerto_pwa_install_dismissed', 'true');
            window.dispatchEvent(new CustomEvent('alerto:installed'));
            console.log('[ALERTO PWA] App was successfully installed.');
        });

        // Register Service Worker
        this.registerServiceWorker();

        return this;
    }

    async promptInstall() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log('[ALERTO PWA] Install prompt outcome:', outcome);
            this.deferredPrompt = null;
            return outcome === 'accepted';
        }
        return false;
    }

    dismissInstall() {
        localStorage.setItem('alerto_pwa_install_dismissed', 'true');
        window.dispatchEvent(new CustomEvent('alerto:install-dismissed'));
    }

    registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js', { scope: '/' })
                    .then((registration) => {
                        console.log('[ALERTO PWA] Service Worker registered with scope:', registration.scope);

                        // Listen for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            if (newWorker) {
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        window.dispatchEvent(new CustomEvent('alerto:sw-updated'));
                                    }
                                });
                            }
                        });
                    })
                    .catch((err) => {
                        console.warn('[ALERTO PWA] Service Worker registration failed:', err);
                    });
            });
        }
    }
}

export default new PwaInstaller();

