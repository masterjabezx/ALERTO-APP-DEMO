# ALERTO EOC — Admin Command Center Design System

## 1. Visual Philosophy
The ALERTO Emergency Operations Center (EOC) interface presents Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO) dispatchers and administrators with a high-contrast tactical command workspace styled in `Plus Jakarta Sans` (UI typography) and `JetBrains Mono` (tabular telemetry, counters, coordinates, and incident reference IDs).

The platform strictly preserves ALERTO APP's four account roles (`citizen`, `personnel`, `office`, `admin`) without any Super Admin tier.

## 2. Official Color Tokens (`resources/css/admin-command-center.css`)
- **Primary Brand Navy:** `--eoc-navy: #173957`
- **Deep Command Background:** `--eoc-deep-navy: #0B1726`
- **Elevated Dark Card Surface:** `--eoc-dark-surface: #101E2E`
- **Primary Operational Blue:** `--eoc-blue: #3187D8`
- **Telemetry Cyan Accent:** `--eoc-cyan: #34B9D4`
- **Critical / Emergency Alert Red:** `--eoc-red: #DB4545`
- **Active / Resolved Emerald:** `--eoc-green: #19865B`
- **Warning / Pending Triage Amber:** `--eoc-amber: #F0A82E`
- **High-Contrast Dark-Mode Text:** `--eoc-light-text: #EDF4FC`
- **Muted Telemetry Text:** `--eoc-muted-text: #93A4B8`

## 3. Dual Theme Architecture (`ThemeManager.js`)
The Admin Shell (`#eoc-admin-shell` in `resources/views/components/layouts/admin.blade.php` and `layouts/admin.blade.php`) applies stored theme preferences synchronously prior to first paint to prevent white-flash glitches:

| Mode | Shell Class | Surface BG | Primary Text | Status Storage Key |
| :--- | :--- | :--- | :--- | :--- |
| **Dark Command Center (Default)** | `.eoc-shell.theme-dark` | `#0B1726` / `#101E2E` | `#EDF4FC` | `localStorage['alerto_admin_theme'] = 'dark'` |
| **Executive Light Mode** | `.eoc-shell.theme-light` | `#F5F7FA` / `#FFFFFF` | `#0F172A` | `localStorage['alerto_admin_theme'] = 'light'` |

Switching themes via `window.AlertoEoc.theme.toggle()` immediately swaps tokens, adapts form inputs/badges/tables across all Admin sub-pages, and dispatches the custom `eoc:theme-changed` browser event so Chart.js canvases re-tint tooltips, gridlines, and legends without reloading the page.

## 4. Reusable Surface & Tactical Utilities
- `.eoc-card`: Rounded `20px` operational panel with subtle border highlight transitions on hover.
- `.eoc-panel-glass` / `.eoc-topbar`: Translucent `12px` backdrop-blurred tactical overlay panels.
- `.eoc-tactical-grid`: Subtle `36px x 36px` telemetry coordinate grid overlay used in hero and HUD contexts.
- `.eoc-chart-well`: Theme-adaptive chart container (`rgba(4, 10, 18, 0.28)` in dark mode, `#F8FAFC` in light mode).
- `.eoc-glow-critical` / `.eoc-glow-active` / `.eoc-glow-info`: Restrained box-shadow indicators reserved exclusively for live emergency alerts and focal telemetry containers.

