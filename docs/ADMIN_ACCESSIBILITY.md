# ALERTO EOC — Accessibility & Operational Ergonomics Guide

## 1. Reduced-Motion Support (`prefers-reduced-motion: reduce`)
Full compliance is enforced simultaneously in CSS and JavaScript:
- **CSS (`resources/css/admin-command-center.css`):** Under `@media (prefers-reduced-motion: reduce)`, `.eoc-scanline` animation is disabled/hidden and transitions/animations inside `.eoc-shell *` clamp to `0.01ms`.
- **JavaScript (`AdminMotionEngine.js`):** Listens live to `window.matchMedia('(prefers-reduced-motion: reduce)')`:
  - Skips entrance staggers, counters snap instantaneously (`StatisticsAnimations`), ambient particle animation does not run, `ScrollTrigger` reveals resolve immediately cleanly, and `EocGlobeVisualizer` switches automatically to static 2D Canvas rendering.

## 2. Contrast & Color Semantics
- **Dual-Mode Palette Contrast:** Dark Command Mode uses `#EDF4FC` light text on `#0B1726` / `#101E2E` surfaces; Executive Light Mode uses `#0F172A` / `#334155` / `#475569` text on `#FFFFFF` / `#F5F7FA` surfaces.
- **Tinted Card & Badge Adaptation:** `.eoc-shell.theme-dark` automatically adapts light-surface Tailwind utility classes across existing sub-modules (`incidents`, `personnel`, `offices`, `messages`, `media`) so tinted alerts (`amber`, `blue`, `emerald`, `purple`, `rose`, `red`) maintain legible foreground text and borders in dark environments.
- **Non-Color Indicators:** Priority states, incident statuses, and connection states pair color cues with explicit text labels (`CRITICAL`, `PENDING_REVIEW`, `online`, `idle`, `connection_lost`) and distinct Lucide icons.

## 3. Keyboard Navigation & Semantic Markup
- **Skip Link:** The top of `#eoc-admin-shell` provides an accessible skip link (`Skip to main command workspace` targeting `#eoc-main-content` with `tabindex="-1"`).
- **Focus Indicators:** `.eoc-shell` enforces `:focus-visible` outlines (`2px solid var(--eoc-cyan)`, `outline-offset: 2px`) across links, buttons, inputs, selects, and textareas.
- **ARIA Labels & Dialog Semantics:** Topbar controls, sidebar toggle, Notification Drawer (`role="dialog"`, `aria-modal="true"`, `Escape` close handler), and dashboard landmark sections include explicit `aria-label` attributes.

