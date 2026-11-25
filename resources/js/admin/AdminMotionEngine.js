import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { Observer } from 'gsap/Observer';

gsap.registerPlugin(ScrollTrigger, Observer);

/**
 * ALERTO EOC — Centralized GSAP Motion Orchestrator
 * Manages GPU-accelerated animations, ScrollTriggers, Observers, and cleanup.
 */
class AdminMotionEngineClass {
    constructor() {
        this.tweens = new Set();
        this.timelines = new Set();
        this.observers = new Set();
        this.scrollTriggers = new Set();
        this.mediaQuery = typeof window !== 'undefined'
            ? window.matchMedia('(prefers-reduced-motion: reduce)')
            : null;
        this.prefersReducedMotion = this.mediaQuery ? this.mediaQuery.matches : false;

        if (this.mediaQuery) {
            this.mediaQuery.addEventListener('change', (e) => {
                this.prefersReducedMotion = e.matches;
                if (this.prefersReducedMotion) {
                    this.cleanup();
                }
            });
        }
    }

    isReducedMotion() {
        return this.prefersReducedMotion;
    }

    trackTween(tween) {
        if (!tween) return tween;
        this.tweens.add(tween);
        tween.eventCallback('onComplete', () => this.tweens.delete(tween));
        return tween;
    }

    createTimeline(options = {}) {
        const tl = gsap.timeline(options);
        this.timelines.add(tl);
        tl.eventCallback('onComplete', () => this.timelines.delete(tl));
        return tl;
    }

    createObserver(config) {
        if (this.prefersReducedMotion) return null;
        const obs = Observer.create(config);
        this.observers.add(obs);
        return obs;
    }

    createScrollReveal(targets, vars = {}) {
        const els = gsap.utils.toArray(targets);
        if (!els.length) return;
        if (this.prefersReducedMotion) {
            gsap.set(els, { opacity: 1, y: 0, clearProps: 'all' });
            return;
        }

        const tween = gsap.from(els, {
            opacity: 0,
            y: 16,
            duration: 0.45,
            stagger: 0.06,
            ease: 'power2.out',
            clearProps: 'transform,opacity',
            scrollTrigger: {
                trigger: els[0],
                start: 'top 92%',
                once: true,
            },
            ...vars,
        });

        if (tween.scrollTrigger) {
            this.scrollTriggers.add(tween.scrollTrigger);
        }
        return this.trackTween(tween);
    }

    cleanup() {
        this.tweens.forEach((t) => {
            try { t.kill(); } catch { /* noop */ }
        });
        this.timelines.forEach((tl) => {
            try { tl.kill(); } catch { /* noop */ }
        });
        this.observers.forEach((obs) => {
            try { obs.kill(); } catch { /* noop */ }
        });
        this.scrollTriggers.forEach((st) => {
            try { st.kill(); } catch { /* noop */ }
        });
        this.tweens.clear();
        this.timelines.clear();
        this.observers.clear();
        this.scrollTriggers.clear();
    }
}

export const AdminMotionEngine = new AdminMotionEngineClass();
export { gsap, ScrollTrigger, Observer };
export default AdminMotionEngine;

