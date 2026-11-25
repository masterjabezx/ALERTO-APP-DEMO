import AdminMotionEngine, { gsap } from './AdminMotionEngine.js';

/**
 * ALERTO EOC — Dashboard Hero, Staggered Grids & Ambient Particle Canvas
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

        // Capped particle count (max 34) for low CPU/GPU overhead
        const count = Math.min(34, Math.floor(this.canvas.width / 32));
        this.particles = Array.from({ length: count }, () => ({
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            vx: (Math.random() - 0.5) * 0.35,
            vy: (Math.random() - 0.5) * 0.25,
            r: Math.random() * 1.6 + 0.7,
            alpha: Math.random() * 0.45 + 0.15,
        }));
    }

    start() {
        if (this.isRunning) return;
        this.isRunning = true;
        const loop = (ts) => {
            if (!this.isRunning) return;
            // Throttle to ~30fps for ambient background
            if (ts - this.lastFrameTime > 32) {
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
            this.ctx.fillStyle = `rgba(52, 185, 212, ${p.alpha})`;
            this.ctx.fill();

            // Subtle nearby telemetry links
            for (let j = i + 1; j < this.particles.length; j++) {
                const p2 = this.particles[j];
                const dx = p.x - p2.x;
                const dy = p.y - p2.y;
                const dist = Math.hypot(dx, dy);
                if (dist < 110) {
                    this.ctx.beginPath();
                    this.ctx.moveTo(p.x, p.y);
                    this.ctx.lineTo(p2.x, p2.y);
                    this.ctx.strokeStyle = `rgba(49, 135, 216, ${(1 - dist / 110) * 0.14})`;
                    this.ctx.lineWidth = 0.8;
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
        const hero = root.querySelector('.eoc-hero-section:not([data-eoc-animated])');
        const kpiCards = root.querySelectorAll('.eoc-kpi-card:not([data-eoc-animated])');
        const activityCards = root.querySelectorAll('.eoc-reveal-panel:not([data-eoc-animated])');

        if (hero) hero.dataset.eocAnimated = '1';
        kpiCards.forEach((el) => { el.dataset.eocAnimated = '1'; });
        activityCards.forEach((el) => { el.dataset.eocAnimated = '1'; });

        if (!AdminMotionEngine.isReducedMotion()) {
            const tl = AdminMotionEngine.createTimeline();

            if (hero) {
                tl.from(hero, { opacity: 0, y: 12, duration: 0.45, ease: 'power2.out', clearProps: 'transform,opacity' });
            }

            if (kpiCards.length) {
                tl.from(kpiCards, {
                    opacity: 0,
                    y: 16,
                    scale: 0.98,
                    duration: 0.4,
                    stagger: 0.045,
                    ease: 'power2.out',
                    clearProps: 'transform,opacity',
                }, hero ? '-=0.25' : 0);
            }

            if (activityCards.length) {
                AdminMotionEngine.createScrollReveal(activityCards);
            }
        }

        const canvas = root.querySelector('#eoc-ambient-particles');
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

