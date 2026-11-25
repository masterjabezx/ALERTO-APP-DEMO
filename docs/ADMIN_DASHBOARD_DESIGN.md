# ALERTO EOC — Dashboard Design & Real Database Modules

## 1. Unified Command Shell (`components/layouts/admin.blade.php` & `layouts/admin.blade.php`)
Both legacy Blade `<x-layouts.admin>` and `@extends('layouts.admin')` templates share the unified `#eoc-admin-shell` architecture backed by an `AppServiceProvider` View Composer injecting real-time `$shellTelemetry` from `App\Services\AdminCommandCenterService`:
- **4 Categorized Navigation Groups (All 12+ Primary Modules Connected):**
  - *OPERATIONS:* Dashboard (`admin.dashboard`), Incident Management (`admin.incidents.index` + live pending badge), Live Operations Map (`admin.map`), Media & Evidence (`admin.media.index`).
  - *FORCES & PRESENCE:* Personnel Management (`admin.personnel.index`), Office Management (`admin.offices.index`), Live User Monitor (`admin.monitor.users`).
  - *COMMUNICATIONS:* Internal Messenger (`admin.messages.index` + live unread count), Facebook Messenger (`admin.communications.fb.inbox` + live unread/intake badge).
  - *GOVERNANCE & SYSTEM:* Command Center Settings (`admin.eoc-settings`), Reports & Analytics (`admin.reports.index`), Activity Logs (`admin.audit.index`), System Settings (`admin.settings.index`).
- **Topbar Controls:** Live telemetry indicator, collapsible sidebar toggle (persisted in `localStorage`), real-time command clock, Dark/Light Theme Switcher, and Slide-Over Admin Notification Center drawer (`POST /admin/notifications/{id}/read`, `POST /admin/notifications/read-all`).

## 2. Main Command Dashboard (`resources/views/dashboards/admin.blade.php`)
All hardcoded placeholder feeds and stats were eliminated and replaced by live MariaDB telemetry:
- **10+ Live Database KPIs:** Total Incidents (plus conditional 7-day trend delta only when prior baseline > 0), Pending Review, Accepted, Dispatched, Active Field, Resolved, Rejected, plus Online Personnel, Online Offices, and Online Citizens (`user_presences`).
- **Average Triage & Resolution Readout:** Real minute averages computed from `submitted_at`, `accepted_at`, and `resolved_at`.
- **Priority Emergency Queue & Live EOC Activity Stream:** Polls `/admin/dashboard/telemetry` every 25 seconds without interrupting filters.
- **6 Interactive Chart.js Visualizations (`ChartAnimations.js`):** Monthly Trends (Submitted vs Resolved), Incidents by Category, Top Barangays, Status Distribution, Office Agency Activity, and Personnel Dispatch Activity—with live animated filtering by Date Range, Category, and Barangay.

## 3. Newly Implemented Functional Admin Modules (`AdminCommandCenterController`)
- **Live User Monitor (`/admin/monitor/users`):** Displays online/idle/connection_lost/offline account sessions across Admin, Office, Personnel, and Citizen accounts. Strictly omits physical GPS coordinates for Citizen accounts.
- **Reports & Analytics (`/admin/reports` & `/reports/export`):** Multi-filter operational analytics workspace with streaming memory-safe CSV export.
- **Activity Logs (`/admin/activity-logs`):** Unified searchable explorer over `audit_logs` and operational `activity_logs`.
- **Command Center Settings (`/admin/eoc-settings`) & System Settings (`/admin/settings`):** Validated via `UpdateEocSettingsRequest` and `UpdateSystemSettingsRequest`, persisting updates to `system_settings` with `AuditLog` tracking.

