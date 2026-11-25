# ALERTO APP — PWA, Offline Capabilities & Data Synchronization Architecture

## 1. Overview & Objective
ALERTO APP has been converted into an installable, mobile-friendly **Progressive Web App (PWA)** engineered for high-reliability emergency operations under zero-connectivity or degraded network conditions in the Municipality of Hinoba-an, Negros Occidental.

The implementation strictly adheres to the core emergency principle:
> **Critical Submission Rule:** An offline report or response action is strictly a locally stored draft or queued action. It is **never** represented to the user or system as received, accepted, or dispatched until the Laravel Command Center confirms atomic server receipt and generates an official reference number.

---

## 2. PWA Manifest & App Installation

### Web App Manifest (`public/manifest.json`)
- **Application Name:** `ALERTO APP — Emergency Operations`
- **Short Name:** `ALERTO`
- **Display Mode:** `standalone` (removes browser URL bar and navigational chrome)
- **Orientation:** `portrait`
- **Theme Color:** `#173957` (Navy ALERTO Command)
- **Background Color:** `#0B1726` (Deep Navy EOC Background)
- **Icons (`public/icons/`):**
  - `icon-192.png`: 192x192 PNG for home screen icons
  - `icon-512.png`: 512x512 PNG for high-res displays and splash screens
  - `icon-maskable.png`: 512x512 maskable PNG with 80% safe zone for adaptive Android icons
  - `icon.svg`: Scalable vector emblem with ALERTO shield, emergency beacon, and cross
- **App Shortcuts:** Direct links to `Report Incident` (`/dashboard`) and `Emergency Hotlines` (`/citizen/contacts`).

### Custom Install Experience (`PwaInstaller.js`, `x-pwa-install-banner`)
- **Android / Desktop Chrome / Edge:** Captures `beforeinstallprompt`, displays a branded install notification bar with a 1-click **Install Now** trigger (`deferredPrompt.prompt()`).
- **iOS Safari:** Detects iOS WebKit and displays contextual instructions: *"Tap Share (square with arrow) then select 'Add to Home Screen'"*.
- **Display Mode Detection:** Automatically hides the install banner when running in `standalone` display mode (`window.matchMedia('(display-mode: standalone)').matches` or `navigator.standalone`).
- **Dismissal Persistence:** Dismissal state is preserved in `localStorage['alerto_pwa_install_dismissed']`.

---

## 3. Service Worker & Caching Strategy (`public/sw.js`)

The service worker implements targeted caching rules tailored for municipal emergency operations:

| Resource Type | Cache Strategy | Description |
|---|---|---|
| **Static Build Assets** (`/build/*`, fonts, icons, `/favicon.ico`) | **Cache-First** | Served immediately from `alerto-pwa-v1` cache; network update runs in background. |
| **HTML Page Navigations** (`/dashboard`, `/records`, etc.) | **Network-First** | Tries live server first. If network is unreachable, falls back to cached page or `/offline` fallback page. |
| **Emergency Fallback Page** (`/offline`) | **Pre-Cached** | Always available offline with active `tel:` link to DIAL DAPH COMMAND CENTER (`0917-123-4567`). |
| **Sensitive & Auth Routes** (`/login`, `/logout`, `/api/*`, `/pwa/sync/*`, `/messages/*`) | **Network-Only (NEVER CACHED)** | Service worker **never** stores authentication tokens, credentials, private chat messages, or confidential records in public caches. |

---

## 4. Shared Network & Connectivity Status (`NetworkManager.js`)

Browser `navigator.onLine` alone is **never** trusted as proof of server reachability. `NetworkManager` monitors 5 distinct conditions:

```
[Online] <──────> [Reconnecting] <──────> [Offline]
   │                      │
   ▼                      ▼
[Syncing] ──────> [Sync Failed / Conflict]
```

1. **`online` (Green):** Two-way connectivity verified via periodic heartbeat (`/pwa/ping`).
2. **`offline` (Red):** Browser reports offline or server ping fails. Changes are safely redirected to local IndexedDB storage.
3. **`reconnecting` (Amber):** Network connection restored; actively verifying Laravel server reachability before releasing queued payloads.
4. **`syncing` (Blue):** Atomic upload of pending drafts, photos, videos, and milestone updates in progress.
5. **`sync_failed` (Rose):** Some items encountered validation errors or version conflicts requiring user attention.

---

## 5. Client-Side IndexedDB Storage Architecture (`OfflineStorage.js`)

IndexedDB database `alerto_offline_db` (Version 1) stores structured offline records partitioned strictly by `user_id`:

### Object Stores
1. **`draft_incidents`** (Key: `client_uuid`):
   - Stores citizen incident reports prepared offline.
   - Includes: `idempotency_key`, `category_id`, `caller_phone`, `caller_name`, `barangay_id`, `latitude`, `longitude`, `accuracy`, `location_source`, `street_address`, `landmark`, `description`, `attachments` (raw `Blob` / `File` objects), and `status: 'pending'`.
2. **`personnel_tasks`** (Key: `assignment_id`):
   - Caches active assignments for field responders.
   - Includes: `incident_id`, `reference_number`, `category_name`, `priority`, `barangay_name`, `coordinates`, `current_status`, `version_lock`, and `response_instructions`.
3. **`personnel_queued_updates`** (Key: `client_uuid`):
   - Queues responder operational milestone actions (Acknowledge, On the Way, On Scene, In Progress, Resolved).
   - Includes: `idempotency_key`, `assignment_id`, `milestone`, `expected_version_lock`, `notes`, `coordinates`, and `status: 'pending'`.
4. **`office_cache`** (Key: `cache_key`):
   - Caches read-only office summaries with `cached_at` timestamps.

---

## 6. Portal Offline Behaviors

### A. Citizen Portal (`dashboards/citizen.blade.php`, `citizen/records.blade.php`)
- **Offline Draft Creation:** If citizen submits an emergency report without connectivity, the form is intercepted. The draft and media attachments are saved to IndexedDB.
- **Critical Submission Notice:** A prominent banner displays:
  > *"Your incident report is saved on this device and is waiting to be submitted when your connection returns. The Command Center has not received this report yet."*
  with a direct one-tap call button to **DIAL DAPH COMMAND CENTER** (`0917-123-4567`).
- **Pending Reports View:** Both the dashboard and incident records screen list pending drafts with a badge: `WAITING FOR CONNECTION — NOT RECEIVED`.
- **Automatic Sync:** When connection is restored, `SyncEngine` submits the draft, uploads attachments via `FormData`, receives the official reference number (e.g. `INC-HNO-0052`), and transitions the draft to `Submitted`.

### B. Personnel Portal (`dashboards/personnel.blade.php`)
- **Offline Task Viewing:** Active assignments are cached on mount. Responders can view scene coordinates, instructions, and reporter numbers while offline.
- **Queued Milestones:** Responders can tap milestone actions (Acknowledge, En Route, On Scene, In Progress, Resolved). Updates are queued locally labeled **Not Yet Synchronized**.
- **Involuntary Connection Lost Presence:** When a responder's device loses network connection, presence changes to `connection_lost` (distinct from voluntary offline). Stale GPS positions are never presented as live.
- **Optimistic Locking & Conflict Detection:** Queued updates include `expected_version_lock`. If the incident was reassigned or resolved while offline, the server returns `409 Conflict`, flags the local item as `conflict`, and prevents blind overwrites.

### C. Office Portal (`dashboards/office.blade.php`, `components/layouts/office.blade.php`)
- Displays an **Offline Snapshot Mode** banner.
- Permits read-only review of previously loaded office dashboards and incident feeds.
- Disables incident reviews, rejections, and dispatch authorizations while offline (`:disabled="!$store.pwa.isOnline"`).

### D. Admin Portal (`components/layouts/admin.blade.php`)
- Topbar status pill updates to **EOC Offline — Snapshot Mode**.
- Disables emergency-critical actions (incident acceptance, unit dispatch, global settings changes, database maintenance).
- Never queues automated dispatch orders for future execution.

---

## 7. Backend Synchronization & Idempotency (`PwaSyncService`, `PwaController`)

### Database Table: `pwa_sync_logs`
Tracks every client synchronization attempt:
- `idempotency_key` (Unique string 64)
- `user_id` (Foreign key to `users`)
- `action_type` (`incident_report`, `personnel_milestone`)
- `status` (`received`, `processed`, `conflict`, `failed`)
- `request_payload` (JSON)
- `response_payload` (JSON)
- `ip_address`, `user_agent`, timestamps

### Idempotency Enforcement (`incidents.idempotency_key`)
- When a citizen syncs an offline draft, the server checks if an incident with `$idempotencyKey` already exists.
- If it exists, the server returns `200 OK` with `is_duplicate => true` and the existing `reference_number` without creating duplicate incident records or duplicate evidence records.

---

## 8. Verification & Test Suite

The PWA and offline synchronization architecture is covered by 15 dedicated feature tests in `tests/Feature/PwaOfflineSyncTest.php`:

```bash
php artisan test --filter=PwaOfflineSyncTest --compact
```

1. `test_pwa_manifest_is_valid_json_with_emergency_configuration` ✅
2. `test_service_worker_file_exists_and_contains_safety_rules` ✅
3. `test_offline_fallback_page_renders_with_dial_daph_hotline` ✅
4. `test_pwa_ping_returns_connectivity_status_and_auth_context` ✅
5. `test_citizen_can_sync_offline_incident_draft` ✅
6. `test_duplicate_draft_submission_prevented_by_idempotency_key` ✅
7. `test_personnel_can_download_authorized_offline_tasks` ✅
8. `test_non_personnel_cannot_access_responder_tasks` ✅
9. `test_personnel_can_sync_milestone_update` ✅
10. `test_milestone_sync_detects_conflict_on_version_lock_mismatch` ✅
11. `test_office_can_download_read_only_offline_summary` ✅
12. `test_connection_lost_reporting_updates_user_presence` ✅
13. `test_guest_cannot_sync_incident_draft` ✅
14. `test_personnel_milestone_sync_is_idempotent_on_retry` ✅
15. `test_unassigned_personnel_cannot_sync_milestone_for_another_responder` ✅

**Overall Application Health:**
- Full Test Suite: **239 tests, 1,332 assertions (100% passing)**.
- Laravel Pint: **Passed (0 formatting issues)**.
- Vite Production Build: **3.35s (0 errors)**.

