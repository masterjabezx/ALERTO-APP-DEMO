# ALERTO APP — Official Application Icon & Cross-Platform Branding Guide

**Municipality of Hinoba-an — LDRRMO / DIAL DAPH Emergency Operations**

This document catalogs the official ALERTO APP brand identity assets, web favicons, Progressive Web App (PWA) icons, iOS Apple Touch icons, and Android launcher resources integrated across the Laravel ecosystem.

---

## 1. Visual Identity & Official Palette

The official ALERTO APP master emblem combines a glossy red **"A"** enclosing an illuminated emergency warning siren, orbital motion swoosh, metallic white **"ALERTO"** wordmark with red location-pin **"O"**, **"APP"** banner, and red/blue emergency wave accents over a deep navy background.

| Brand Color | Hex | Usage |
|---|---|---|
| **Deep Navy Background** | `#0B1726` | PWA `theme_color`, `background_color`, `<meta name="theme-color">`, Android `@color/ic_launcher_background`, favicon & icon canvas |
| **Emergency Red** | `#DB4545` | Primary "A" identity, siren highlights, orbital swoosh accents, SOS controls |
| **Signal Blue** | `#3187D8` | Accent waves, active navigation & command center indicators |
| **Metallic White** | `#FFFFFF` | High-contrast "ALERTO" & "APP" lettering, siren glow rays |

### Preserved Branding Rules
1. Keep `object-contain` when rendering the emblem inside containers to prevent stretching or distortion.
2. Never crop the "ALERTO" wordmark or "APP" banner in full logo badges.
3. For tiny browser tab favicons (16×16, 32×32, 48×48, SVG), use the simplified **A + Siren + Swoosh** emblem (`alerto-favicon-*`) rather than tiny illegible text.
4. Keep the original uploaded master reference image unmodified in `resources/source-assets/alerto-official-icon-master.jpg`.

---

## 2. Source Artwork & Regenerating Assets

- **Unmodified Reference JPEG:** `resources/source-assets/alerto-official-icon-master.jpg` (1024×1024)
- **Lossless Master PNG Copy:** `public/images/branding/alerto-logo-source.png` (1024×1024)
- **Transparent Web Squircle Master:** `public/images/branding/alerto-logo-badge.png` (1024×1024, alpha-masked outer corners)

---

## 3. Centralized Icon & Asset Inventory

### A. Website & Portal UI Badges (`public/images/branding/`)

| File | Dimensions | Usage |
|---|---|---|
| `alerto-logo-web-512.png` | 512×512 | High-resolution web marketing / showcase asset |
| `alerto-logo-web-256.png` | 256×256 | Login cards (`citizen`, `register`, `personnel`, `office`, `admin`, `super-admin`, `admin-setup`) & `/offline` beacon badge |
| `alerto-logo-web-128.png` | 128×128 | Top navigation headers, sidebars, welcome page header, and PWA install banner |
| `alerto-mark-128.png` | 128×128 | Simplified A+Siren emblem mark |

### B. Browser Favicons (`public/` & `public/images/branding/`)

| File | Dimensions / Format | Usage |
|---|---|---|
| `/favicon.ico` | 16×16, 32×32, 48×48 multi-frame ICO | Root browser fallback favicon |
| `alerto-favicon.ico` | 16×16, 32×32, 48×48 multi-frame ICO | Canonical multi-frame ICO copy |
| `alerto-favicon.svg` | Scalable SVG | Modern vector favicon emphasizing red **A** + glowing warning siren |
| `alerto-favicon-48.png` | 48×48 PNG | Desktop shortcut / Windows site pin favicon |
| `alerto-favicon-32.png` | 32×32 PNG | Standard retina browser tab favicon |
| `alerto-favicon-16.png` | 16×16 PNG | Compact classic browser tab favicon |

### C. PWA & Android Manifest Icons (`public/images/branding/`)

Referenced in `public/manifest.json` and pre-cached in `public/sw.js` (`CACHE_NAME = 'alerto-pwa-v2'`):

| File | Dimensions | `purpose` | Usage |
|---|---|---|---|
| `alerto-app-icon-1024.png` | 1024×1024 | — | High-resolution store/splash master |
| `alerto-app-icon-512.png` | 512×512 | `any` | PWA splash screen & Android Chrome install |
| `alerto-app-icon-384.png` | 384×384 | — | Intermediate store/PWA asset |
| `alerto-app-icon-256.png` | 256×256 | — | Desktop shortcut / medium launcher asset |
| `alerto-app-icon-192.png` | 192×192 | `any` | PWA app icon & manifest shortcuts |
| `alerto-app-icon-maskable-512.png` | 512×512 | `maskable` | Android adaptive maskable safe-zone (65% core scale) |
| `alerto-app-icon-maskable-192.png` | 192×192 | `maskable` | Compact Android adaptive maskable safe-zone |

### D. iOS & iPadOS Apple Touch Icons (`public/images/branding/`)

Referenced via `<x-pwa-meta />` (`resources/views/components/pwa-meta.blade.php`):

| File | Dimensions | Target Device |
|---|---|---|
| `alerto-apple-touch-icon.png` | 180×180 | iPhone Retina ("Add to Home Screen" default) |
| `alerto-apple-touch-icon-167.png` | 167×167 | iPad Pro |
| `alerto-apple-touch-icon-152.png` | 152×152 | iPad / iPad mini |
| `alerto-apple-touch-icon-120.png` | 120×120 | Older iPhone 2x / fallback |

### E. Native Android Launcher Resource Bundle (`public/images/branding/android/res/`)

> **Note:** This repository does not currently contain a Capacitor, Cordova, or native Android Studio project directory. The resource tree under `public/images/branding/android/res/` is packaged as a drop-in Android `app/src/main/res/` launcher bundle ready for APK/AAB builds when an Android wrapper is added.

| Path | Dimensions / Type | Role |
|---|---|---|
| `mipmap-mdpi/ic_launcher.png`, `ic_launcher_round.png` | 48×48 PNG | Standard & circular mdpi launcher icons |
| `mipmap-hdpi/ic_launcher.png`, `ic_launcher_round.png` | 72×72 PNG | Standard & circular hdpi launcher icons |
| `mipmap-xhdpi/ic_launcher.png`, `ic_launcher_round.png` | 96×96 PNG | Standard & circular xhdpi launcher icons |
| `mipmap-xxhdpi/ic_launcher.png`, `ic_launcher_round.png` | 144×144 PNG | Standard & circular xxhdpi launcher icons |
| `mipmap-xxxhdpi/ic_launcher.png`, `ic_launcher_round.png` | 192×192 PNG | Standard & circular xxxhdpi launcher icons |
| `drawable-xxxhdpi/ic_launcher_foreground.png` | 432×432 PNG | Transparent Android 8+ adaptive foreground layer |
| `drawable-xxxhdpi/ic_launcher_monochrome.png` | 432×432 PNG | Android 13+ themed monochrome icon layer |
| `mipmap-anydpi-v26/ic_launcher.xml`, `ic_launcher_round.xml` | XML | `<adaptive-icon>` definitions linking background, foreground, and monochrome layers |
| `values/ic_launcher_background.xml` | XML | `#0B1726` adaptive background color resource |
| `values/strings_branding.xml` | XML | `<string name="app_name">ALERTO APP</string>` |

---

## 4. Application Integration Summary

- **Meta Partial:** `resources/views/components/pwa-meta.blade.php` defines the manifest, `#0B1726` theme color, all ICO/SVG/PNG favicons, and Apple Touch Icons.
- **Included In:**
  - `resources/views/welcome.blade.php`
  - `resources/views/layouts/auth.blade.php` (covers all login, registration, setup, and guest report/SOS views)
  - `resources/views/pwa/offline.blade.php`
  - `resources/views/components/layouts/{citizen,office,personnel,admin,super-admin}.blade.php`
  - `resources/views/layouts/{citizen,office,personnel}.blade.php`
- **Portal Headers & Login UI:** Updated across Citizen, Personnel, Office, DIAL DAPH Command Center Admin, LDRRMO Super Admin, Guest/Welcome, Offline Page, and PWA Install Banner (`resources/views/components/pwa-install-banner.blade.php`).
- **Service Worker Cache:** `public/sw.js` upgraded to `alerto-pwa-v2` to invalidate cached legacy icons and cache `/images/branding/*` assets Cache-First, without altering IndexedDB (`alerto_offline_db`) offline drafts, SOS alerts, or queued sync records.
- **Legacy Icons Compatibility:** Legacy `/public/icons/` files remain preserved temporarily alongside `/public/images/branding/` to avoid breaking stale browser caches during SW rollout.

