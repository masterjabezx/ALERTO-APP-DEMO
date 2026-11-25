import AdminMotionEngine, { gsap } from './AdminMotionEngine.js';

/**
 * ALERTO EOC — Sidebar & Navigation Animations
 */
export const NavigationAnimations = {
    animateSidebarMount(sidebarEl) {
        if (!sidebarEl || AdminMotionEngine.isReducedMotion()) return;
        const items = sidebarEl.querySelectorAll('.eoc-nav-link');
        if (!items.length) return;

        const tween = gsap.from(items, {
            opacity: 0,
            x: -10,
            duration: 0.3,
            stagger: 0.025,
            ease: 'power2.out',
            clearProps: 'transform,opacity',
        });

        return AdminMotionEngine.trackTween(tween);
    },

    animateActivePulse(indicatorEl) {
        if (!indicatorEl || AdminMotionEngine.isReducedMotion()) return;
        const tween = gsap.fromTo(
            indicatorEl,
            { scaleY: 0.4, opacity: 0.5 },
            { scaleY: 1, opacity: 1, duration: 0.25, ease: 'back.out(2)' }
        );
        return AdminMotionEngine.trackTween(tween);
    },
};

export default NavigationAnimations;

