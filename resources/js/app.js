import './demo-safety';
import './bootstrap';
import Alpine from 'alpinejs';
import { AlertoAnim } from './animations';
import { initializeIcons } from './icons';
import AdminMotionEngine from './admin/AdminMotionEngine';
import ThemeManager from './admin/ThemeManager';
import DashboardAnimations from './admin/DashboardAnimations';
import StatisticsAnimations from './admin/StatisticsAnimations';
import NavigationAnimations from './admin/NavigationAnimations';
import ChartAnimations from './admin/ChartAnimations';
import EocGlobeVisualizer from './admin/EocGlobeVisualizer';
import {
    IncidentAnimations,
    NotificationAnimations,
    MapAnimations,
    MessengerAnimations,
    ModalAnimations,
} from './admin/OperationsMotions';
import OfflineStorage from './pwa/OfflineStorage';
import NetworkManager from './pwa/NetworkManager';
import SyncEngine from './pwa/SyncEngine';
import PwaInstaller from './pwa/PwaInstaller';

// Expose globals for Alpine.js & Blade inline templates
window.Alpine = Alpine;
window.AlertoAnim = AlertoAnim;
window.refreshIcons = initializeIcons;
window.AlertoPwa = {
    storage: OfflineStorage,
    network: NetworkManager,
    sync: SyncEngine,
    installer: PwaInstaller,
};
window.AlertoEoc = {
    motion: AdminMotionEngine,
    theme: ThemeManager,
    dashboard: DashboardAnimations,
    stats: StatisticsAnimations,
    nav: NavigationAnimations,
    charts: ChartAnimations,
    globe: EocGlobeVisualizer,
    incident: IncidentAnimations,
    notifications: NotificationAnimations,
    map: MapAnimations,
    messenger: MessengerAnimations,
    modal: ModalAnimations,
};

// Apply stored theme cleanly before Alpine mounts on any .alerto-shell / #eoc-admin-shell
const portalShell = document.querySelector('.alerto-shell') || document.getElementById('eoc-admin-shell');
if (portalShell) {
    ThemeManager.applyTheme(ThemeManager.getStoredTheme(), false);
}

// Register global Alpine theme helper across all portals
Alpine.store('theme', {
    current: ThemeManager.getStoredTheme(),
    isDark: ThemeManager.getStoredTheme() === 'dark',
    init() {
        window.addEventListener('alerto:theme-changed', (e) => {
            this.current = e.detail?.theme || 'dark';
            this.isDark = !!e.detail?.isDark;
        });
    },
    toggle() {
        ThemeManager.toggle();
    },
});

// Custom Alpine Directives
Alpine.directive('animate-entrance', (el) => {
    AlertoAnim.animateEntrance(el);
});

Alpine.directive('count-up', (el, { expression }) => {
    const endValue = parseFloat(expression) || parseFloat(el.textContent) || 0;
    AlertoAnim.animateCountUp(el, endValue);
});

Alpine.directive('lucide', (el) => {
    initializeIcons(el.parentElement || document);
});

// Initialize PWA and Network services
PwaInstaller.init();
NetworkManager.init();

// Register Alpine PWA Global Store
Alpine.store('pwa', {
    status: NetworkManager.status,
    isOnline: NetworkManager.isOnline(),
    pendingCount: 0,
    canInstall: false,
    isIos: PwaInstaller.isIos,
    isStandalone: PwaInstaller.isStandalone,
    lastConnected: null,
    lastSynced: null,
    drawerOpen: false,
    init() {
        NetworkManager.subscribe((detail) => {
            this.status = detail.status;
            this.isOnline = detail.isOnline;
            this.lastConnected = detail.lastConnectedAt;
            this.lastSynced = detail.lastSyncedAt;
            this.pendingCount = detail.pendingCount;
        });
        window.addEventListener('alerto:pending-count', (e) => {
            this.pendingCount = e.detail.count;
        });
        window.addEventListener('alerto:can-install', (e) => {
            this.canInstall = true;
        });
        window.addEventListener('alerto:installed', () => {
            this.canInstall = false;
        });
    },
    promptInstall() {
        PwaInstaller.promptInstall();
    },
    dismissInstall() {
        PwaInstaller.dismissInstall();
        this.canInstall = false;
    },
    syncNow(userId) {
        return SyncEngine.syncAll(userId);
    }
});

Alpine.start();

// Initialize Lucide icons & Admin Sidebar / Subpage Motion on boot
document.addEventListener('DOMContentLoaded', () => {
    initializeIcons();
    const sidebar = document.getElementById('eoc-sidebar');
    if (sidebar) {
        NavigationAnimations.animateSidebarMount(sidebar);
    }

    const portalShell = document.querySelector('.alerto-shell') || document.getElementById('eoc-admin-shell');
    if (portalShell && !document.getElementById('eoc-globe-canvas')) {
        DashboardAnimations.init(document);
        StatisticsAnimations.animateAllCounters(document);
    }

    // Clear read-only cached operational snapshots on explicit logout and warn on pending unsynced drafts
    document.querySelectorAll('form[action$="/logout"]').forEach((form) => {
        form.addEventListener('submit', async (e) => {
            if (!window.AlertoPwa?.storage) return;
            try {
                const ping = await fetch('/pwa/ping', { cache: 'no-store' }).then((r) => r.ok ? r.json() : null).catch(() => null);
                const uid = ping?.user?.id;
                if (uid) {
                    await OfflineStorage.clearCachedSnapshots(uid);
                }
            } catch (_) {}
        });
    });
});

// Cleanup animations cleanly when unloading
window.addEventListener('beforeunload', () => {
    AdminMotionEngine.cleanup();
    DashboardAnimations.destroy();
    ChartAnimations.destroyAll();
    EocGlobeVisualizer.dispose();
});
