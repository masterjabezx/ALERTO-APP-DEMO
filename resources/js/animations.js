/**
 * ALERTO APP — GSAP Advanced Animation Engine
 * Specialized for Hinoba-an LDRRMO Emergency Operations Center
 * Follows reduced-motion and zero-delay operational guidelines.
 */

import { gsap } from 'gsap';

class AlertoAnimationEngine {
    constructor() {
        this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.activeTweens = new Set();
        
        // Listen for user OS motion preferences changes
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
            this.prefersReducedMotion = e.matches;
        });
    }

    /**
     * Staggered entrance animation for dashboard widgets and cards
     */
    animateEntrance(targets, options = {}) {
        if (this.prefersReducedMotion || !targets) return;

        const defaults = {
            duration: 0.5,
            opacity: 0,
            y: 18,
            stagger: 0.06,
            ease: 'power2.out',
            clearProps: 'all',
        };

        const config = { ...defaults, ...options };
        const tween = gsap.from(targets, config);
        this.activeTweens.add(tween);
        tween.eventCallback('onComplete', () => this.activeTweens.delete(tween));
        return tween;
    }

    /**
     * Smooth statistic count-up animation
     */
    animateCountUp(target, endValue, duration = 1.2) {
        if (!target) return;
        const finalNum = parseFloat(endValue) || 0;

        if (this.prefersReducedMotion) {
            target.textContent = finalNum.toLocaleString();
            return;
        }

        const obj = { val: 0 };
        const tween = gsap.to(obj, {
            val: finalNum,
            duration: Math.min(duration, 1.4),
            ease: 'power1.out',
            onUpdate: () => {
                target.textContent = Math.round(obj.val).toLocaleString();
            },
            onComplete: () => {
                target.textContent = finalNum.toLocaleString();
                this.activeTweens.delete(tween);
            }
        });

        this.activeTweens.add(tween);
        return tween;
    }

    /**
     * Notification / Toast slide-in and auto-dismiss animation
     */
    animateToast(target, onComplete) {
        if (!target) return;

        if (this.prefersReducedMotion) {
            gsap.set(target, { opacity: 1 });
            return;
        }

        return gsap.fromTo(target, 
            { opacity: 0, y: -20, scale: 0.95 },
            { 
                opacity: 1, 
                y: 0, 
                scale: 1, 
                duration: 0.35, 
                ease: 'back.out(1.4)',
                onComplete: () => {
                    if (typeof onComplete === 'function') onComplete();
                }
            }
        );
    }

    /**
     * Modal entrance scale + fade
     */
    animateModal(modalBox, backdrop, isOpen) {
        if (this.prefersReducedMotion) return;

        if (isOpen) {
            if (backdrop) gsap.fromTo(backdrop, { opacity: 0 }, { opacity: 1, duration: 0.25 });
            if (modalBox) {
                gsap.fromTo(modalBox, 
                    { opacity: 0, scale: 0.94, y: 12 }, 
                    { opacity: 1, scale: 1, y: 0, duration: 0.3, ease: 'power3.out' }
                );
            }
        }
    }

    /**
     * Tab switch content cross-fade
     */
    animateTabTransition(target) {
        if (this.prefersReducedMotion || !target) return;

        gsap.fromTo(target,
            { opacity: 0, y: 10 },
            { opacity: 1, y: 0, duration: 0.25, ease: 'power2.out', clearProps: 'transform' }
        );
    }

    /**
     * Table row loading entrance
     */
    animateTableRows(rows) {
        if (this.prefersReducedMotion || !rows || rows.length === 0) return;

        return gsap.from(rows, {
            opacity: 0,
            y: 8,
            duration: 0.3,
            stagger: 0.04,
            ease: 'power2.out',
            clearProps: 'all'
        });
    }

    /**
     * Cleanup on SPA view destroy or component unmount
     */
    cleanup() {
        this.activeTweens.forEach(t => t.kill());
        this.activeTweens.clear();
    }
}

export const AlertoAnim = new AlertoAnimationEngine();
export default AlertoAnim;

