# ALERTO EOC — Performance & Resource Optimization Guide

## 1. Vite Bundle Code Splitting (`vite.config.js`)
To keep initial page loads lean and prevent monolithic JS bundles from blocking non-chart pages, `build.rollupOptions.output.manualChunks` isolates heavy visualization libraries into cacheable vendor chunks, paired with dynamic `import()` in JS modules:
- `vendor-gsap`: GSAP core + ScrollTrigger + Observer plugins.
- `vendor-chartjs`: Chart.js library (`chart.js/auto`), dynamically imported on demand inside `ChartAnimations.ensureChartLib()`.
- `vendor-three`: Three.js WebGL engine (`three`), dynamically imported only when `#eoc-globe-canvas` is present and WebGL is supported (`EocGlobeVisualizer.mount()`).

## 2. Frame-Budget & GPU Discipline
- **Capped Frame Rates for Background Visuals:**
  - `AmbientCanvasField` throttles animation ticks to ~30fps (`> 32ms` threshold) and limits particles to a hard maximum of `34`.
  - `EocGlobeVisualizer` caps renderer pixel ratio (`Math.min(window.devicePixelRatio || 1, 1.75)`), uses `powerPreference: 'low-power'`, and caps frame interval (~33fps).
- **Visibility & Viewport Suspension:**
  - `document.visibilitychange` pauses `AmbientCanvasField` and `EocGlobeVisualizer` loops immediately when the browser tab is hidden.
  - `IntersectionObserver` (`threshold: 0.05`) suspends Three.js WebGL draw calls whenever the hero globe scrolls out of view.
- **Targeted Re-Animations:** `DashboardAnimations.init()` guards elements with `data-eoc-animated="1"` so dynamic Alpine updates never re-trigger initial mount entrance sequences.

## 3. Clean Disposal
On `beforeunload`, `resources/js/app.js` sequentially calls:
- `AdminMotionEngine.cleanup()`
- `DashboardAnimations.destroy()`
- `ChartAnimations.destroyAll()`
- `EocGlobeVisualizer.dispose()` (disposes WebGL context, cancels RAF loops, disconnects observers)

