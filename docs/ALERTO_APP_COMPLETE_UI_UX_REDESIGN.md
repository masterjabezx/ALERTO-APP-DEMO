# ALERTO APP — Complete Website UI/UX Redesign Architecture

## 1. Hinoba-an Orange Design System & Palette Tokens

All four operational portals (**LDRRMO Super Admin**, **DIAL DAPH Command Center Admin**, **Office Portal**, **Personnel Portal**), Citizen/Guest flows, PWA offline fallback, and login gateways have been unified under the **Hinoba-an Orange, Carbon Black, Graphite, Warm Ivory, and Pure White** visual language:

| Role / Semantic Token | Hex Value | Usage |
|---|---|---|
| Primary Brand (Hinoba-an Orange) | `#E87524` | Primary active navigation pills, CTAs, highlights, chart defaults, brand focus rings |
| Readable Orange on Light Surface | `#B94F12` | High-contrast small text links, badges, reference highlights on Ivory/White |
| Soft Orange on Dark Surface | `#F29A56` | Readable orange accent labels, badges, icon accents on Carbon/Black |
| Carbon Black / Deep Black | `#17191C` / `#101214` | Primary operational headers, dark surfaces, high-contrast command widgets |
| Graphite Surface | `#292D32` | Elevated dark containers, cards, secondary action surfaces |
| Warm Ivory Canvas / Pure White | `#F5F3EF` / `#FFFFFF` | Light Mode page canvas and `.alerto-card` surfaces |
| Semantic Success | `#27845B` / `#5BD19A` | Responder `ONLINE` duty state, GPS lock confirmation, call action, resolved status |
| Semantic Warning | `#D99024` / `#F6BA53` | Pending triage, offline cached/queued indicators, deployment notices |
| Emergency Critical Red | `#C83F43` / `#F27D80` | Distinct from brand orange — reserved for SOS distress, life-safety report CTA, critical priority, destructive actions |
| Informational Accent | `#397BA8` / `#7BB5DB` | Secondary informational notices and telemetry callouts |

A workspace scan across `resources/views`, `resources/css`, and `resources/js` (excluding `components/pwa-meta.blade.php` and `resources/js/generate-icons.cjs`) verified **zero remaining legacy blue/navy hex values** (`#173957`, `#3187D8`, `#0B1726`, `#34B9D4`, `#0f2438`, `#1f4b72`, `#122e46`).

---

## 2. Official Brand Artwork & Dual Theme Architecture

- **Official ALERTO Logo Preservation:** `/images/branding/alerto-logo-web-128.png`, `/images/branding/alerto-logo-web-256.png`, `/images/branding/alerto-favicon.svg`, and `/images/branding/alerto-apple-touch-icon.png` are preserved unmodified with `object-contain` across all headers, login gateways, and offline screens. The icon canvas color in `resources/views/components/pwa-meta.blade.php` (`#0B1726`) and `resources/js/generate-icons.cjs` remains intact.
- **Dual Light/Dark Mode Engine:**
  - Powered by `.alerto-shell`, CSS variables (`--surface-bg`, `--surface-card`, `--surface-panel`, `--surface-hover`, `--text-primary`, `--text-secondary`, `--text-muted`, `--border-subtle`, `--border-strong`, `--brand-primary`), `resources/js/admin/ThemeManager.js`, and `<x-theme-toggle />` (`$store.theme?.toggle()`, `$store.theme?.isDark`, persisted in `localStorage`).
  - Field and public portals (`office`, `personnel`, `citizen`) initialize with `data-default-theme="light"` (`theme-light` Warm Ivory default) for bright daylight readability while allowing operators to switch to Dark Mode for low-light operations.

---

## 3. Transformed Modules & Components

### A. Core Stylesheets, Motion & Shared Components
- Stylesheets: `resources/css/app.css`, `resources/css/admin-command-center.css`
- Scripts & Motion Modules: `resources/js/app.js`, `resources/js/admin/{ThemeManager,DashboardAnimations,EocGlobeVisualizer,ChartAnimations,OperationsMotions}.js`
- UI Components: `button`, `badge`, `card`, `stat-card`, `input`, `password-input`, `select`, `table`, `modal`, `confirm-dialog`, `dropdown`, `empty-state`, `filter-panel`, `search-bar`, `skeleton`, `toast`, `incident-card`, `map-container`, `pwa-install-banner`, `pwa-connection-indicator`, `sos-button-widget`, `file-upload`, `dual-hotlines`, plus added `textarea`, `theme-toggle`, `error-state`, `portal/{breadcrumbs,page-header}`, and `auth/split-login`.

### B. Login Pages & Portal Separation
- Transformed `welcome.blade.php`, `layouts/auth.blade.php`, and `auth/{citizen-login,citizen-register,personnel-login,office-login,admin-login,super-admin-login,admin-setup}.blade.php`.
- Preserved strict **Citizen-only access isolation** on `/` and `/login` (no links/buttons exposing Super Admin, Admin, Office, or Personnel login routes).

### C. All Four Operational Portals + Citizen, Guest & PWA Views
1. **LDRRMO Super Admin Portal:** `components/layouts/super-admin.blade.php`, `super-admin/{dashboard,monitoring}.blade.php`, `super-admin/{admins,routing,backups}/index.blade.php`.
2. **DIAL DAPH Command Center Admin Portal:** `components/layouts/admin.blade.php`, `dashboards/admin.blade.php`, `admin/incidents/{index,show,evidence}.blade.php` (including fast clickable Category Cards synced to `category_id` in `admin/incidents/show.blade.php`), `admin/map/{index,boundaries}.blade.php`, `admin/offices/{index,create,edit,users}.blade.php`, `admin/personnel/{index,create,edit,assignments,availability,bases,jurisdictions,teams}.blade.php`, `admin/{incoming,dispatch,sos,citizens,notifications,audit,reports,eoc-settings,settings,media,messages}/*.blade.php`, `admin/monitor/users.blade.php`, and `admin/communications/facebook-messenger/{inbox,settings}.blade.php`.
3. **Office Portal:** `components/layouts/office.blade.php` (added mobile drawer & theme toggle), `layouts/office.blade.php`, `office/dashboard.blade.php`, `dashboards/office.blade.php`, `office/incidents/{index,show}.blade.php`, and `office/{map,personnel,records,messages}.blade.php`.
4. **Personnel Portal:** `components/layouts/personnel.blade.php`, `layouts/personnel.blade.php`, `dashboards/personnel.blade.php`, and `personnel/{availability,active-incidents,map,messages,records}.blade.php`.
5. **Citizen, Guest & Capture Modules:** `components/layouts/citizen.blade.php`, `layouts/citizen.blade.php`, `dashboards/citizen.blade.php` (category-free rapid emergency intake preserved), `citizen/{contacts,records,record-detail,success}.blade.php`, `guest/{report,sos,confirmation,sos-confirmation}.blade.php`, `pwa/offline.blade.php`, `components/citizen-geo-media-capture.blade.php`, and `components/emergency-location-picker.blade.php` (non-touch `gpsOnly` display map preserved).

---

## 4. Root Cause & Resolution: Personnel Online / Offline Duty Bug

- **Root Cause (`resources/views/personnel/availability.blade.php`):** `executeSubmit()` was declared without the `async` keyword while containing `await fetch(url, ...)`, throwing a JavaScript `SyntaxError` that prevented `window.availabilityForm()` from registering or handling card clicks and form submissions.
- **Resolution:**
  - Updated `executeSubmit()` to `async executeSubmit()` with `credentials: 'same-origin'`, explicit `X-CSRF-TOKEN` and `Accept: application/json`, an 8-second `AbortController` timeout, clean `finally` timeout cleanup, and `this.submitting = false` recovery on failures.
  - Upgraded the `ONLINE` (`#27845B`) and `OFFLINE` (`#E87524`) duty cards to proper accessible `<button type="button">` elements with focus-visible rings, disabled state during submission, connection-lost notice (`$store.pwa`), and preserved `'Personnel profile required'` handling when `!$personnel`.

---

## 5. Verification Summary (Observed Run Outputs)

| Check | Command / Tool | Observed Result |
|---|---|---|
| Legacy Color Scan | PowerShell `Select-String` across `resources/views`, `resources/css`, `resources/js` (excl. `pwa-meta.blade.php`, `generate-icons.cjs`) | **Passed:** `NO LEGACY HEXES FOUND` |
| Production Frontend Build | `npm run build` (`vite v7.3.8`) | **Passed:** 2,201 modules transformed; assets generated in `public/build/` in 44.98s |
| Blade Compilation | `php artisan view:cache` & `php artisan view:clear` | **Passed:** `Blade templates cached successfully.` / `Compiled views cleared successfully.` |
| PHP Code Style | `php vendor/bin/pint --dirty --format agent` | **Passed:** `{"tool":"pint","result":"passed"}` |
| Unit Tests | `php artisan test tests/Unit --compact` | **Passed:** `1 passed (1 assertions)` (`Tests\Unit\ExampleTest`) |
| Full PHPUnit Suite | `php artisan test --compact` | **Blocked on DB test grant:** `278 failed, 2 passed` — feature tests fail connecting to `alerto_app_testing` (`SQLSTATE[HY000] [1044] Access denied for user 'alerto_user'@'localhost' to database 'alerto_app_testing'` under pre-existing `phpunit.xml` config) |
| Live Browser Visual QA | `browser` subagent (`chrome_devtools` MCP against `http://localhost`) | **Blocked:** Chrome remote debugging unavailable (`ENOENT` for `C:\Users\ACER NITRO\AppData\Local\Google\Chrome\User Data\DevToolsActivePort`). Enable remote debugging at `chrome://inspect/#remote-debugging` in Chrome to run live multi-viewport inspection |

