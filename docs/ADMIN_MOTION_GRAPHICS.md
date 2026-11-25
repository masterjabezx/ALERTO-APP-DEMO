# ALERTO EOC — Admin Motion Graphics Architecture

## 1. Motion Principles
All motion inside the ALERTO Admin Portal serves operational situational awareness—directing dispatcher attention cleanly toward new reports, critical emergencies, and spatial map updates while avoiding gratuitous visual noise:
1. **GPU-Composited Transforms Only:** Animations target CSS `transform` (`translate`, `scale`) and `opacity`, clearing inline transform styles after completion (`clearProps: 'transform,opacity'`).
2. **Real-Time Responsiveness:** UI transitions stay between `0.24s` and `0.65s` with `power2.out` / `power3.out` easing so rapid emergency workflows are never delayed.
3. **Automatic Lifecycle & Reduced-Motion Respect:** Every GSAP tween, ScrollTrigger, Observer, and particle RAF loop is registered inside `AdminMotionEngine.js` and automatically pauses or completes immediately when `prefers-reduced-motion: reduce` is enabled.

## 2. Modular JavaScript File Map (`resources/js/admin/`)
| Module | Responsibility |
| :--- | :--- |
| `AdminMotionEngine.js` | Central GSAP `3.15.1` + `ScrollTrigger` + `Observer` orchestrator, reduced-motion watcher, and full `cleanup()`. |
| `ThemeManager.js` | Persistent Dark/Light theme state manager + Chart.js palette token provider. |
| `DashboardAnimations.js` | Hero reveal sequence, staggered `.eoc-kpi-card` entrance (guarded by `data-eoc-animated`), ScrollTrigger section reveals, and FPS-throttled `AmbientCanvasField`. |
| `StatisticsAnimations.js` | Tabular `countUp()` numeric counters (`[data-eoc-count]`) and SVG circular stroke ring tweens. |
| `NavigationAnimations.js` | Staggered sidebar `.eoc-nav-link` mount reveal and active route indicator pulse. |
| `ChartAnimations.js` | Lazy-loaded Chart.js (`chart.js/auto`) suite across 6 operational datasets with animated filter updates and theme synchronization. |
| `EocGlobeVisualizer.js` | Lazy-loaded Three.js (`three`) interactive 3D command sphere HUD with `IntersectionObserver` pause and 2D radar Canvas fallback. |
| `OperationsMotions.js` | Exports `IncidentAnimations`, `NotificationAnimations`, `MapAnimations`, `MessengerAnimations`, and `ModalAnimations`. |

## 3. Global Entrypoint (`resources/js/app.js`)
`window.AlertoEoc` exposes all Admin motion controllers globally for Blade & Alpine.js integration without altering `window.AlertoAnim` used on Citizen, Personnel, and Office portals:
- `NavigationAnimations.animateSidebarMount()` boots automatically on `#eoc-sidebar`.
- `DashboardAnimations.init(document)` and `StatisticsAnimations.animateAllCounters(document)` boot automatically on Admin sub-pages that do not manually invoke dashboard-specific Globe/Chart setup (`#eoc-globe-canvas`).
- Messenger bubble animations (`MessengerAnimations.animateBubble`) run when Internal Messenger and Facebook Messenger containers scroll to the active message stream.
- All animations and WebGL/Canvas contexts dispose cleanly during `beforeunload`.

