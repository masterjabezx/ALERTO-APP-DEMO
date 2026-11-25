import AdminMotionEngine, { gsap } from './AdminMotionEngine.js';

/**
 * ALERTO APP — Portal Hero, Staggered Grids & Subtle Hinoba-an Ambient Particle Field
 */
class AmbientCanvasField {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas ? canvas.getContext('2d') : null;
        this.particles = [];
        this.rafId = null;
        this.isRunning = false;
        this.lastFrameTime = 0;

        if (this.canvas && this.ctx && !AdminMotionEngine.isReducedMotion()) {
            this.initParticles();
            this.start();
            this.bindVisibility();
        }
    }

    initParticles() {
        const rect = this.canvas.parentElement?.getBoundingClientRect();
        this.canvas.width = Math.max(300, Math.floor(rect?.width || 800));
        this.canvas.height = Math.max(180, Math.floor(rect?.height || 260));

        const isMobile = window.innerWidth < 768;
        const count = isMobile ? 14 : Math.min(28, Math.floor(this.canvas.width / 40));
        this.particles = Array.from({ length: count }, () => ({
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            vx: (Math.random() - 0.5) * 0.25,
            vy: (Math.random() - 0.5) * 0.20,
            r: Math.random() * 1.5 + 0.6,
            alpha: Math.random() * 0.38 + 0.14,
            isOrange: Math.random() > 0.35,
        }));
    }

    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        const loop = (ts) => {
            if (!this.isRunning) return;
            if (ts - this.lastFrameTime > 36) {
                this.lastFrameTime = ts;
                this.draw();
            }
            this.rafId = requestAnimationFrame(loop);
        };
        this.rafId = requestAnimationFrame(loop);
    }

    draw() {
        if (!this.ctx || !this.canvas) return;
        const { width, height } = this.canvas;
        this.ctx.clearRect(0, 0, width, height);

        for (let i = 0; i < this.particles.length; i++) {
            const p = this.particles[i];
            p.x += p.vx;
            p.y += p.vy;

            if (p.x < 0) p.x = width;
            if (p.x > width) p.x = 0;
            if (p.y < 0) p.y = height;
            if (p.y > height) p.y = 0;

            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            this.ctx.fillStyle = p.isOrange
                ? `rgba(232, 117, 36, ${p.alpha})`
                : `rgba(245, 243, 239, ${p.alpha * 0.6})`;
            this.ctx.fill();

            for (let j = i + 1; j < this.particles.length; j++) {
                const p2 = this.particles[j];
                const dist = Math.hypot(p.x - p2.x, p.y - p2.y);
                if (dist < 105) {
                    this.ctx.beginPath();
                    this.ctx.moveTo(p.x, p.y);
                    this.ctx.lineTo(p2.x, p2.y);
                    this.ctx.strokeStyle = `rgba(232, 117, 36, ${(1 - dist / 105) * 0.13})`;
                    this.ctx.lineWidth = 0.75;
                    this.ctx.stroke();
                }
            }
        }
    }

    bindVisibility() {
        this._onVis = () => {
            if (document.hidden) {
                this.stop();
            } else if (!AdminMotionEngine.isReducedMotion()) {
                this.start();
            }
        };
        document.addEventListener('visibilitychange', this._onVis);
    }

    stop() {
        this.isRunning = false;
        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
    }

    destroy() {
        this.stop();
        if (this._onVis) {
            document.removeEventListener('visibilitychange', this._onVis);
        }
    }
}

export const DashboardAnimations = {
    ambientField: null,

    init(root = document) {
        const hero = root.querySelector('.eoc-hero-section:not([data-eoc-animated]), .alerto-hero-section:not([data-eoc-animated])');
        const kpiCards = root.querySelectorAll('.eoc-kpi-card:not([data-eoc-animated]), .alerto-kpi-card:not([data-eoc-animated])');
        const revealPanels = root.querySelectorAll('.eoc-reveal-panel:not([data-eoc-animated]), .alerto-reveal-panel:not([data-eoc-animated])');

        if (hero) hero.dataset.eocAnimated = '1';
        kpiCards.forEach((el) => { el.dataset.eocAnimated = '1'; });
        revealPanels.forEach((el) => { el.dataset.eocAnimated = '1'; });

        if (!AdminMotionEngine.isReducedMotion()) {
            const tl = AdminMotionEngine.createTimeline();

            if (hero) {
                tl.from(hero, { opacity: 0, y: 10, duration: 0.42, ease: 'power2.out', clearProps: 'transform,opacity' });
            }

            if (kpiCards.length) {
                tl.from(kpiCards, {
                    opacity: 0,
                    y: 12,
                    duration: 0.35,
                    stagger: 0.04,
                    ease: 'power2.out',
                    clearProps: 'transform,opacity',
                }, hero ? '-=0.22' : 0);
            }

            if (revealPanels.length) {
                AdminMotionEngine.createScrollReveal(revealPanels);
            }
        }

        const canvas = root.querySelector('#eoc-ambient-particles, #alerto-ambient-particles');
        if (canvas) {
            if (this.ambientField) this.ambientField.destroy();
            this.ambientField = new AmbientCanvasField(canvas);
        }
    },

    destroy() {
        if (this.ambientField) {
            this.ambientField.destroy();
            this.ambientField = null;
        }
    },
};

export default DashboardAnimations;
