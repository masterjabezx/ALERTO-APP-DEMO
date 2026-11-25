# ALERTO EOC — Three.js & Canvas Visual Components

## 1. Important Operational Boundary
The 3D visualization in `resources/js/admin/EocGlobeVisualizer.js` (`#eoc-globe-canvas`) is a **decorative and interactive EOC telemetry HUD sphere**. All operational incident markers, responder GPS pins, barangay boundaries, evidence attachments, and turn-by-turn routing remain strictly anchored to real database coordinates on **Leaflet GIS** (`/admin/map`).

## 2. 3D WebGL Telemetry Sphere (`EocGlobeVisualizer.js`)
Using dynamic `import('three')` (`three@^0.187.0`), the visualizer constructs:
1. **Inner Telemetry Core:** Deep navy translucent sphere (`SphereGeometry(1.12, 24, 24)`).
2. **Geodesic Wireframe Shell:** Operational blue wireframe (`IcosahedronGeometry(1.18, 2)`).
3. **Equatorial & Inclined Orbital Rings:** Rotating cyan (`#34B9D4`) and status-colored (`#DB4545` when critical alerts are active, otherwise `#19865B`) `TorusGeometry` rings.
4. **Telemetry Sector Nodes:** Four orbital beacons representing sector status readiness.
5. **Interactive Drag Rotation:** Controlled via GSAP `Observer` (`type: 'pointer,touch'`) interpolating `targetRotY` and clamped `targetRotX`.

## 3. Graceful Degradation & Fallbacks
- When WebGL (`webgl2` / `webgl`) is unavailable or `(prefers-reduced-motion: reduce)` matches, `EocGlobeVisualizer.renderCanvas2DFallback()` draws an immediate static 2D Canvas tactical radar target with center status beacon and zero console errors.
- Dashboard hero indicator reflects the active engine cleanly (`3D EOC TELEMETRY HUD · WEBGL` vs `2D RADAR`).

## 4. Ambient Particle Field (`AmbientCanvasField`)
- Mounted on `#eoc-ambient-particles` inside the dashboard hero section.
- Particle count capped cleanly (`Math.min(34, Math.floor(width / 32))`) with subtle proximity vector strokes (`< 110px`).
- Throttled to ~30fps (`> 32ms` frame interval) and automatically stopped whenever `document.hidden` is true or reduced motion is enabled.

