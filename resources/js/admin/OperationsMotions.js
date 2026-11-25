import AdminMotionEngine, { gsap } from './AdminMotionEngine.js';

/**
 * ALERTO EOC — Incident, Notification, Map, Messenger & Modal Motion Modules
 */
export const IncidentAnimations = {
    animateArrival(element) {
        if (!element || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(
            element,
            { opacity: 0, x: -12, backgroundColor: 'rgba(232, 117, 36, 0.16)' },
            { opacity: 1, x: 0, backgroundColor: 'transparent', duration: 0.55, ease: 'power2.out', clearProps: 'transform,opacity' }
        );
        return AdminMotionEngine.trackTween(tween);
    },

    pulseCriticalBadge(badgeEl) {
        if (!badgeEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(badgeEl, { scale: 0.94 }, { scale: 1.04, duration: 0.4, repeat: 1, yoyo: true, ease: 'power1.inOut' });
        return AdminMotionEngine.trackTween(tween);
    },
};

export const NotificationAnimations = {
    animateDrawerOpen(panelEl) {
        if (!panelEl || AdminMotionEngine.isReducedMotion()) return;
        const items = panelEl.querySelectorAll('.eoc-notif-item');
        if (items.length) {
            const tween = gsap.from(items, {
                opacity: 0,
                x: 16,
                duration: 0.28,
                stagger: 0.03,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
            });
            return AdminMotionEngine.trackTween(tween);
        }
    },

    bumpCounter(badgeEl) {
        if (!badgeEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(badgeEl, { scale: 0.7 }, { scale: 1, duration: 0.3, ease: 'back.out(2.2)' });
        return AdminMotionEngine.trackTween(tween);
    },
};

export const MapAnimations = {
    animateOverlayEntry(container = document) {
        if (AdminMotionEngine.isReducedMotion()) return;
        const hud = container.querySelectorAll('.eoc-map-hud');
        if (hud.length) {
            const tween = gsap.from(hud, {
                opacity: 0,
                y: -10,
                duration: 0.35,
                stagger: 0.06,
                ease: 'power2.out',
                clearProps: 'transform,opacity',
            });
            return AdminMotionEngine.trackTween(tween);
        }
    },

    animatePanelSlideIn(panelEl) {
        if (!panelEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(panelEl, { x: 40, opacity: 0 }, { x: 0, opacity: 1, duration: 0.28, ease: 'power2.out', clearProps: 'transform,opacity' });
        return AdminMotionEngine.trackTween(tween);
    },
};

export const MessengerAnimations = {
    animateBubble(bubbleEl) {
        if (!bubbleEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(bubbleEl, { opacity: 0, y: 10, scale: 0.98 }, { opacity: 1, y: 0, scale: 1, duration: 0.26, ease: 'power2.out', clearProps: 'transform,opacity' });
        return AdminMotionEngine.trackTween(tween);
    },
};

export const ModalAnimations = {
    open(modalEl) {
        if (!modalEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(modalEl, { opacity: 0, scale: 0.95, y: 10 }, { opacity: 1, scale: 1, y: 0, duration: 0.24, ease: 'power3.out', clearProps: 'transform,opacity' });
        return AdminMotionEngine.trackTween(tween);
    },
};

export default {
    IncidentAnimations,
    NotificationAnimations,
    MapAnimations,
    MessengerAnimations,
    ModalAnimations,
};

