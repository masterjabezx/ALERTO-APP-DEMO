# ALERTO APP — Animation Guidelines & Performance Standards

## 1. Principles of Operational Animation

In emergency response software, animations must **never delay information comprehension or user action**. Every transition is purposeful, informing the user of state changes without slowing down mission-critical tasks.

- **Speed Standards:**
  - Micro-interactions (hover, active press): `150ms - 200ms`
  - Dialog / Drawer Entrances: `250ms - 300ms`
  - Page & Card Entrance Staggers: max `500ms` total duration
  - Numerical Stat Count-Up: max `1.2s` with ease-out curve

---

## 2. GSAP Animation Engine (`resources/js/animations.js`)

The centralized `AlertoAnim` singleton wraps GSAP with safe defaults and memory leak prevention:

```javascript
import { AlertoAnim } from './animations';

// Stagger cards on load
AlertoAnim.animateEntrance(document.querySelectorAll('.stat-widget'), {
    y: 16,
    stagger: 0.05,
    duration: 0.4
});

// Smooth count-up
AlertoAnim.animateCountUp(document.getElementById('incident-counter'), 42);

// Modal and drawer transitions
AlertoAnim.animateModal(modalBox, backdrop, true);
```

---

## 3. Alpine.js Custom Directives

- `x-animate-entrance`: Automatically attaches staggered entrance when mounted.
- `x-count-up="number"`: Triggers GSAP numerical roll-up.
- `x-lucide`: Ensures icons re-render on reactive DOM mutations.

---

## 4. Reduced-Motion Compliance (WCAG 2.1 AAA)

Every animation inspects `window.matchMedia('(prefers-reduced-motion: reduce)')`. When reduced motion is preferred by the operating system:
1. GSAP tweens bypass transitional offsets and apply final values immediately (`0.01ms`).
2. CSS transitions and ping keyframes are globally squashed via `@media (prefers-reduced-motion: reduce)`.
3. Heartbeat status indicators use solid high-contrast colors instead of pulsating rings.

