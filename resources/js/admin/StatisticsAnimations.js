import AdminMotionEngine, { gsap } from './AdminMotionEngine.js';

/**
 * ALERTO EOC — Statistics Count-Up & Gauge Motion Module
 */
export const StatisticsAnimations = {
    animateAllCounters(root = document) {
        const nodes = root.querySelectorAll('[data-eoc-count]');
        nodes.forEach((node) => {
            const raw = parseFloat(node.dataset.eocCount || '0');
            this.countUp(node, isNaN(raw) ? 0 : raw);
        });
    },

    countUp(targetEl, endValue, duration = 0.9) {
        if (!targetEl) return;
        const target = Math.max(0, Number(endValue) || 0);

        if (AdminMotionEngine.isReducedMotion()) {
            targetEl.textContent = target.toLocaleString();
            return;
        }

        const current = parseFloat((targetEl.dataset.currentValue || '0')) || 0;
        const state = { val: current };
        const tween = gsap.to(state, {
            val: target,
            duration: duration,
            ease: 'power2.out',
            onUpdate: () => {
                targetEl.textContent = Math.round(state.val).toLocaleString();
            },
            onComplete: () => {
                targetEl.textContent = target.toLocaleString();
                targetEl.dataset.currentValue = String(target);
            },
        });

        return AdminMotionEngine.trackTween(tween);
    },

    animateCircularProgress(circleEl, percent = 0) {
        if (!circleEl) return;
        const radius = parseFloat(circleEl.getAttribute('r') || '24');
        const circumference = 2 * Math.PI * radius;
        const clamped = Math.max(0, Math.min(100, percent));
        const offset = circumference - (clamped / 100) * circumference;

        circleEl.style.strokeDasharray = `${circumference} ${circumference}`;

        if (AdminMotionEngine.isReducedMotion()) {
            circleEl.style.strokeDashoffset = String(offset);
            return;
        }

        const tween = gsap.fromTo(
            circleEl,
            { strokeDashoffset: circumference },
            { strokeDashoffset: offset, duration: 0.9, ease: 'power2.out' }
        );

        return AdminMotionEngine.trackTween(tween);
    },
};

export default StatisticsAnimations;

