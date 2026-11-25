/**
 * ALERTO APP — Shared Network and Connection Status Manager
 * Distinguishes: Online, Offline, Reconnecting, Synchronizing, Sync Failed.
 * Validates browser navigator.onLine with actual server reachability checks.
 */

class NetworkManager {
    constructor() {
        this.status = navigator.onLine ? 'online' : 'offline';
        this.lastConnectedAt = navigator.onLine ? new Date() : null;
        this.lastSyncedAt = null;
        this.pendingCount = 0;
        this.checkTimer = null;
        this.isChecking = false;
        this.listeners = new Set();
    }

    init() {
        // Browser network event listeners
        window.addEventListener('online', () => this.handleBrowserOnline());
        window.addEventListener('offline', () => this.handleBrowserOffline());

        // Initial server verification
        this.verifyServerConnection();

        // Periodic healthcheck: Every 45s when online, every 12s when offline/reconnecting
        this.scheduleHealthCheck();

        return this;
    }

    getStatus() {
        return {
            status: this.status,
            isOnline: this.status === 'online' || this.status === 'syncing',
            lastConnectedAt: this.lastConnectedAt,
            lastSyncedAt: this.lastSyncedAt,
            pendingCount: this.pendingCount
        };
    }

    isOnline() {
        return this.status === 'online' || this.status === 'syncing';
    }

    setStatus(newStatus, errorDetails = null) {
        const previous = this.status;
        this.status = newStatus;

        if (newStatus === 'online') {
            this.lastConnectedAt = new Date();
        }

        const detail = {
            status: this.status,
            previousStatus: previous,
            isOnline: this.isOnline(),
            lastConnectedAt: this.lastConnectedAt,
            lastSyncedAt: this.lastSyncedAt,
            pendingCount: this.pendingCount,
            error: errorDetails
        };

        window.dispatchEvent(new CustomEvent('alerto:network-status', { detail }));
        this.listeners.forEach((callback) => callback(detail));
    }

    setPendingCount(count) {
        this.pendingCount = Math.max(0, count);
        window.dispatchEvent(new CustomEvent('alerto:pending-count', { detail: { count: this.pendingCount } }));
    }

    setLastSynced(date = new Date()) {
        this.lastSyncedAt = date;
    }

    subscribe(callback) {
        this.listeners.add(callback);
        return () => this.listeners.delete(callback);
    }

    async handleBrowserOnline() {
        this.setStatus('reconnecting');
        await this.verifyServerConnection();
    }

    async handleBrowserOffline() {
        this.setStatus('offline');
        // Report connection loss to Laravel presence with CSRF token if beacon supported
        try {
            const formData = new FormData();
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (tokenMeta) {
                formData.append('_token', tokenMeta.getAttribute('content') || '');
            }
            navigator.sendBeacon?.('/pwa/connection-lost', formData);
        } catch (_) {}
    }

    async verifyServerConnection() {
        if (this.isChecking) return;
        this.isChecking = true;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 6000);

            const res = await fetch('/pwa/ping', {
                method: 'GET',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (res.ok) {
                const data = await res.json();
                if (data.status === 'online') {
                    if (this.status !== 'syncing') {
                        this.setStatus('online');
                    }
                    return true;
                }
            }
            this.setStatus('offline');
            return false;
        } catch (err) {
            this.setStatus('offline', err.message);
            return false;
        } finally {
            this.isChecking = false;
        }
    }

    scheduleHealthCheck() {
        if (this.checkTimer) clearTimeout(this.checkTimer);
        const interval = this.isOnline() ? 45000 : 12000;

        this.checkTimer = setTimeout(async () => {
            if (navigator.onLine) {
                await this.verifyServerConnection();
            } else {
                this.setStatus('offline');
            }
            this.scheduleHealthCheck();
        }, interval);
    }
}

export default new NetworkManager();

