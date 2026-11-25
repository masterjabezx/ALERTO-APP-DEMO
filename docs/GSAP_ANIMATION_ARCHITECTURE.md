# ALERTO EOC — GSAP Animation Architecture

## 1. Registered Plugins & Engine Core
Built on `gsap@^3.15.1`, `resources/js/admin/AdminMotionEngine.js` registers:
- `ScrollTrigger` (`gsap/ScrollTrigger`) — triggers `once: true` viewport reveal sequences on `.eoc-reveal-panel` sections (`start: 'top 92%'`).
- `Observer` (`gsap/Observer`) — powers smooth drag/touch inertial rotation on the 3D EOC Telemetry Sphere inside `#eoc-globe-canvas`.

## 2. Tracked Resource Lifecycle
To prevent memory leaks during prolonged EOC monitoring shifts or filter interactions:
- `trackTween(tween)`: Registers active tweens in an internal `Set`, automatically pruning entries via `onComplete`.
- `createTimeline(options)`: Registers timelines in `this.timelines` and prunes on completion.
- `createObserver(config)` & `createScrollReveal(targets, vars)`: Tracks observer/trigger instances so `AdminMotionEngine.cleanup()` kills all tweens, timelines, observers, and scrollTriggers atomically.

## 3. Key Animation Sequences
- **Dashboard Hero & KPI Stagger (`DashboardAnimations.init`):** Marks animated nodes with `data-eoc-animated="1"` to prevent duplicate runs, enters `.eoc-hero-section` (`0.45s`), then staggers `.eoc-kpi-card` elements (`0.045s` stagger).
- **Live Map Overlay & Drawers (`OperationsMotions.js`):**
  - `MapAnimations.animateOverlayEntry()`: Staggers `.eoc-map-hud` tactical controls on map load.
  - `MapAnimations.animatePanelSlideIn()`: Smoothly slides responder telemetry and incident detail drawers from `x: 40`.
- **Notifications & Incident Arrivals (`OperationsMotions.js`):**
  - `NotificationAnimations.animateDrawerOpen()`: Staggers `.eoc-notif-item` rows when opening the topbar Notification Drawer.
  - `IncidentAnimations.animateArrival()`: Highlights newly polled or broadcasted emergency arrivals cleanly without full-page jumps.
- **Modals & Messenger (`OperationsMotions.js`):**
  - `ModalAnimations.open()` scales modals smoothly (`0.95 -> 1`, `0.24s`).
  - `MessengerAnimations.animateBubble()` animates active Internal and Facebook Messenger bubbles cleanly into view.

