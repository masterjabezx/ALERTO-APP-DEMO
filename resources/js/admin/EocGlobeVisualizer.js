import AdminMotionEngine from './AdminMotionEngine.js';

/**
 * ALERTO EOC — 3D WebGL Telemetry Centerpiece (Lazy-Loaded Three.js + 2D Fallback)
 * Decorative HUD visualization of Hinoba-an EOC telemetry state.
 * Note: All actual geographic mapping and incident coordinates reside strictly in Leaflet GIS.
 */
class EocGlobeVisualizerClass {
    constructor() {
        this.renderer = null;
        this.scene = null;
        this.camera = null;
        this.globeGroup = null;
        this.rafId = null;
        this.observer = null;
        this.intersectionObs = null;
        this.isVisible = true;
        this.isWebGlMode = false;
        this.targetRotY = 0;
        this.targetRotX = 0.25;
        this.beaconColor = 0x19865B;
    }

    isWebGLAvailable() {
        try {
            const testCanvas = document.createElement('canvas');
            return !!(
                window.WebGLRenderingContext &&
                (testCanvas.getContext('webgl2') || testCanvas.getContext('webgl'))
            );
        } catch {
            return false;
        }
    }

    async mount(canvasEl, options = {}) {
        if (!canvasEl) return { mode: 'none' };

        this.dispose();
        const criticalCount = Number(options.criticalCount || 0);
        this.beaconColor = criticalCount > 0 ? 0xDB4545 : 0x19865B;

        if (!this.isWebGLAvailable() || AdminMotionEngine.isReducedMotion()) {
            this.renderCanvas2DFallback(canvasEl, criticalCount);
            this.isWebGlMode = false;
            return { mode: 'fallback-2d' };
        }

        try {
            const THREE = await import('three');
            const rect = canvasEl.parentElement?.getBoundingClientRect();
            const width = Math.max(180, Math.floor(rect?.width || 240));
            const height = Math.max(180, Math.floor(rect?.height || 220));

            this.scene = new THREE.Scene();
            this.camera = new THREE.PerspectiveCamera(42, width / height, 0.1, 100);
            this.camera.position.z = 4.2;

            this.renderer = new THREE.WebGLRenderer({
                canvas: canvasEl,
                alpha: true,
                antialias: true,
                powerPreference: 'low-power',
            });
            this.renderer.setSize(width, height, false);
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));

            this.globeGroup = new THREE.Group();
            this.scene.add(this.globeGroup);

            // Inner glowing dark core sphere
            const coreGeo = new THREE.SphereGeometry(1.12, 24, 24);
            const coreMat = new THREE.MeshBasicMaterial({
                color: 0x0B1726,
                transparent: true,
                opacity: 0.72,
            });
            this.globeGroup.add(new THREE.Mesh(coreGeo, coreMat));

            // Wireframe geodesic sphere
            const wireGeo = new THREE.IcosahedronGeometry(1.18, 2);
            const wireMat = new THREE.MeshBasicMaterial({
                color: 0x3187D8,
                wireframe: true,
                transparent: true,
                opacity: 0.36,
            });
            this.globeGroup.add(new THREE.Mesh(wireGeo, wireMat));

            // Outer latitude telemetry ring
            const ringGeo = new THREE.TorusGeometry(1.48, 0.012, 8, 64);
            const ringMat = new THREE.MeshBasicMaterial({
                color: 0x34B9D4,
                transparent: true,
                opacity: 0.55,
            });
            const eqRing = new THREE.Mesh(ringGeo, ringMat);
            eqRing.rotation.x = Math.PI / 2.3;
            this.globeGroup.add(eqRing);

            // Secondary inclined telemetry orbit
            const ring2 = new THREE.Mesh(
                new THREE.TorusGeometry(1.62, 0.008, 8, 64),
                new THREE.MeshBasicMaterial({ color: this.beaconColor, transparent: true, opacity: 0.5 })
            );
            ring2.rotation.y = Math.PI / 3;
            ring2.rotation.x = Math.PI / 4;
            this.globeGroup.add(ring2);

            // Sector nodes around sphere
            const nodeGeo = new THREE.SphereGeometry(0.045, 10, 10);
            const nodeCoords = [
                [0.45, 0.85, 0.65, this.beaconColor],
                [-0.75, 0.45, 0.75, 0x34B9D4],
                [0.88, -0.35, -0.62, 0x3187D8],
                [-0.35, -0.82, 0.72, 0xF0A82E],
            ];

            nodeCoords.forEach(([nx, ny, nz, col]) => {
                const nMesh = new THREE.Mesh(
                    nodeGeo,
                    new THREE.MeshBasicMaterial({ color: col })
                );
                nMesh.position.set(nx, ny, nz);
                this.globeGroup.add(nMesh);
            });

            // Interactive drag rotation via GSAP Observer
            this.observer = AdminMotionEngine.createObserver({
                target: canvasEl,
                type: 'pointer,touch',
                onDrag: (self) => {
                    this.targetRotY += self.deltaX * 0.008;
                    this.targetRotX = Math.max(-0.8, Math.min(0.8, this.targetRotX + self.deltaY * 0.006));
                },
            });

            // Pause rendering when off-screen
            if (typeof IntersectionObserver !== 'undefined') {
                this.intersectionObs = new IntersectionObserver((entries) => {
                    this.isVisible = entries[0]?.isIntersecting ?? true;
                }, { threshold: 0.05 });
                this.intersectionObs.observe(canvasEl);
            }

            let lastTime = 0;
            const animate = (time) => {
                this.rafId = requestAnimationFrame(animate);
                if (!this.isVisible || document.hidden || !this.renderer) return;
                if (time - lastTime < 30) return; // Cap ~33fps for ultra-low GPU draw
                lastTime = time;

                this.targetRotY += 0.0045;
                this.globeGroup.rotation.y += (this.targetRotY - this.globeGroup.rotation.y) * 0.1;
                this.globeGroup.rotation.x += (this.targetRotX - this.globeGroup.rotation.x) * 0.1;
                ring2.rotation.z += 0.006;

                this.renderer.render(this.scene, this.camera);
            };
            this.rafId = requestAnimationFrame(animate);
            this.isWebGlMode = true;
            return { mode: 'webgl' };
        } catch {
            this.renderCanvas2DFallback(canvasEl, criticalCount);
            this.isWebGlMode = false;
            return { mode: 'fallback-2d' };
        }
    }

    renderCanvas2DFallback(canvasEl, criticalCount = 0) {
        const ctx = canvasEl.getContext('2d');
        if (!ctx) return;

        const rect = canvasEl.parentElement?.getBoundingClientRect();
        const size = Math.max(180, Math.floor(Math.min(rect?.width || 220, rect?.height || 220)));
        canvasEl.width = size;
        canvasEl.height = size;

        const cx = size / 2;
        const cy = size / 2;
        const r = size * 0.36;

        ctx.clearRect(0, 0, size, size);

        // Tactical radar circles
        [1, 0.7, 0.4].forEach((scale, idx) => {
            ctx.beginPath();
            ctx.arc(cx, cy, r * scale, 0, Math.PI * 2);
            ctx.strokeStyle = idx === 0 ? 'rgba(52, 185, 212, 0.45)' : 'rgba(49, 135, 216, 0.25)';
            ctx.lineWidth = 1.2;
            ctx.stroke();
        });

        // Crosshairs
        ctx.beginPath();
        ctx.moveTo(cx - r * 1.15, cy);
        ctx.lineTo(cx + r * 1.15, cy);
        ctx.moveTo(cx, cy - r * 1.15);
        ctx.lineTo(cx, cy + r * 1.15);
        ctx.strokeStyle = 'rgba(147, 164, 184, 0.22)';
        ctx.stroke();

        // Center Beacon
        ctx.beginPath();
        ctx.arc(cx, cy, 5, 0, Math.PI * 2);
        ctx.fillStyle = criticalCount > 0 ? '#DB4545' : '#19865B';
        ctx.fill();
    }

    dispose() {
        if (this.rafId !== null) {
            cancelAnimationFrame(this.rafId);
            this.rafId = null;
        }
        if (this.observer) {
            try { this.observer.kill(); } catch { /* noop */ }
            this.observer = null;
        }
        if (this.intersectionObs) {
            this.intersectionObs.disconnect();
            this.intersectionObs = null;
        }
        if (this.renderer) {
            try { this.renderer.dispose(); } catch { /* noop */ }
            this.renderer = null;
        }
        this.scene = null;
        this.camera = null;
        this.globeGroup = null;
    }
}

export const EocGlobeVisualizer = new EocGlobeVisualizerClass();
export default EocGlobeVisualizer;

