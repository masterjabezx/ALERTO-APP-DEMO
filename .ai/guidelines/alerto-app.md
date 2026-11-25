# ALERTO APP — Master AI Development Guidelines

## 1. Project Architecture & Foundation
- **Application Name:** ALERTO APP
- **Core Purpose:** Real-time emergency response, community safety monitoring, geo-incident management, and inter-office coordination platform.
- **Framework & Foundation:**
  - Laravel 13 running on PHP 8.5+
  - Development Database: MariaDB / MySQL (XAMPP Server `127.0.0.1:3306`, Database: `alerto_app`)
  - Character Encoding: `utf8mb4` with `utf8mb4_unicode_ci`
  - Frontend Stack: Blade Components, Tailwind CSS v4, Alpine.js, GSAP (for Admin EOC dashboard), Vite 7
  - Real-Time Communication: WebSockets / Event Broadcasting, Server-Sent Events (SSE), and WebRTC where appropriate.
  - Architecture Pattern: Clean Domain-Driven Service Architecture. Controllers must remain thin, delegating business logic to dedicated Service classes and validation to Form Request classes.

---

## 2. Laravel 13 Development Conventions
- **Strict Typing:** All PHP classes and methods must use strict typing (`declare(strict_types=1);`).
- **Thin Controllers:** Controllers only accept requests, invoke Service classes, and return Blade views or JSON responses.
- **Form Requests:** Never perform inline validation in controllers. Create dedicated Form Request classes (`app/Http/Requests/*`) with strict validation rules.
- **Service Layer:** All business logic, third-party integrations, geospatial calculations, and incident workflows belong in `app/Services/*`.
- **Database Transactions:** All multi-step database mutations must be wrapped inside `DB::transaction()` to ensure atomicity.
- **Eloquent Relationships:** Define explicit return types on all Eloquent model relationships (`BelongsTo`, `HasMany`, `BelongsToMany`, etc.).
- **Reusable Blade Components:** UI elements must be implemented as atomic Blade components in `resources/views/components/*`.
- **Policies & Gates:** Authorization logic must reside strictly inside Laravel Policies (`app/Policies/*`).
- **No Verification Scripts:** Do not leave loose testing scripts in the project root; all validation must be codified in PHPUnit Feature/Unit tests.

---

## 3. Database Design Conventions
- **Naming Conventions:**
  - Table names: Plural snake_case (e.g., `incidents`, `incident_updates`, `personnel_profiles`).
  - Primary keys: Unsigned Big Integer (`id`) or UUIDs where distributed security is required.
  - Foreign keys: Singular model name with `_id` suffix (e.g., `incident_id`, `user_id`, `office_id`) with foreign key constraints and indexed lookup.
- **Timestamps:** All operational tables must maintain standard `created_at` and `updated_at` timestamps.
- **Soft Deletes:** Critical public safety data (incidents, dispatch logs, chat transcripts) must use Soft Deletes (`deleted_at`) for audit compliance.
- **Spatial Indexing:** Geographic latitude and longitude fields must be properly indexed for fast radius and bounding-box queries.

---

## 4. Role-Based Authorization & Strict Account Structure
**STRICT ACCOUNT STRUCTURE — ONLY FOUR (4) ACCOUNT TYPES:**
1. **Citizen:** General public user reporting incidents and receiving localized alerts.
2. **Personnel:** Field emergency responders (police, medics, firefighters, rescue units).
3. **Office:** Departmental dispatchers and desk officers managing operational units and status.
4. **Admin:** The single highest system authority (Emergency Operations Center director/system administrator).

> [!CAUTION]
> **ABSOLUTE RULE:** THERE MUST BE NO SUPER ADMIN ROLE.
> The `Admin` account is the highest system authority. Do not create, suggest, or scaffold any `super_admin`, `root`, or secondary administrative tier.

- Access control must be enforced via dedicated Laravel Middleware (`EnsureUserHasRole`) and strict Policies for every resource.

---

## 5. Secure Authentication
- Session-based web authentication with robust password hashing (Bcrypt, cost 12+).
- Two-Factor Authentication (2FA / OTP) for Personnel, Office, and Admin roles.
- Rate limiting on all authentication routes (`throttle:login`, 5 attempts per minute).
- Immediate session invalidation on logout and strict CSRF protection on all mutating HTTP verbs.

---

## 6. Citizen Portal Requirements
- **Design Philosophy:** Clean, simple, minimalist, mobile-first, and highly accessible under high-stress conditions.
- **Key Capabilities:**
  - One-tap "Report Emergency" action with GPS location grab.
  - Quick incident category selection (Fire, Medical, Crime, Natural Disaster, Traffic Hazard).
  - Geo-tagged photo/video upload.
  - Live incident status tracking (Submitted -> Verified -> Dispatched -> In Progress -> Resolved).
  - Public advisory announcements and safety map.

---

## 7. Personnel Portal Requirements
- **Design Philosophy:** Clean operational interface, high-contrast, distraction-free, mobile-first for field operatives.
- **Key Capabilities:**
  - Real-time dispatch alerts with audible and visual triggers.
  - Turn-by-turn route preview and ETA status reporting (En Route, On Scene, Hospital/Base).
  - Live status toggle (Available, Dispatched, On Scene, Busy, Off Duty).
  - Two-way encrypted field communication with dispatch and attached responders.

---

## 8. Office Portal Requirements
- **Design Philosophy:** Professional, organized, modular, streamlined desktop/tablet interface for departmental officers.
- **Key Capabilities:**
  - Incident triage and queue management.
  - Departmental personnel scheduling, unit assignment, and operational status logs.
  - Incident verification, escalation, and resolution approvals.
  - Analytical reports export (PDF, CSV).

---

## 9. Admin Portal Requirements (Emergency Operations Center)
- **Design Philosophy:** Premium Emergency Operations Center (EOC) Command Dashboard.
- **Visual & UI Standards:**
  - Dark mode by default with cybernetic/tactical emergency aesthetic.
  - Sophisticated GSAP animations for screen entries, stat updates, and live alert tickers.
  - Interactive Live Operations Map (Mapbox/Leaflet with dark vector tiles).
  - Modern live data visualizations and interactive charts (incident heatmaps, response time averages, resolution rates).
  - Smooth micro-interactions and transitions with zero UI jank.

---

## 10. Incident Management Workflow
1. **Submission:** Incident reported via Citizen Portal or ingested via Facebook Messenger webhook.
2. **Triaging & Verification:** Ingested into the triage queue; initial deduplication and geo-radius check.
3. **Dispatch & Routing:** Assigned automatically or manually to the nearest qualified Office/Personnel units.
4. **Field Response:** Responders acknowledge dispatch, update status, and attach on-scene evidence.
5. **Resolution & Post-Incident Review:** Formal closure, citizen notification, and archival in the audit log.

---

## 11. Automatic and Manual Routing
- **Automatic Routing Algorithm:** Calculates proximity, unit availability, incident priority, and jurisdictional boundaries to recommend or auto-assign the closest active responder.
- **Manual Override:** Office dispatchers and EOC Admins retain complete authority to manually re-route or multi-dispatch units at any time.

---

## 12. GIS and GPS Implementation
- WGS84 standard coordinate system (`latitude` and `longitude` decimal precision: 8 decimal places).
- Reverse geocoding services to translate coordinates into human-readable street addresses and landmarks.
- Spatial boundaries (GeoJSON polygons) for municipal barangays, districts, and response zones.

---

## 13. Live Operations Map
- Integrated interactive map in Office and Admin portals.
- Dynamic clustering for high-density incident zones.
- Real-time GPS markers displaying active Personnel units with status indicators (Available: Green, Dispatched: Amber, On Scene: Red).
- Incident radius buffers showing affected perimeter and evacuation zones.

---

## 14. Internal Messenger
- Role-scoped secure messaging channels:
  - Incident Command Channel (Dispatcher + Field Responders on an active incident).
  - Direct 1-on-1 operational chat.
  - Inter-Office coordination broadcast channel.
- Delivery and read receipts with full transcript archiving.

---

## 15. Facebook Messenger Integration
- Official webhook integration with Meta Messenger API.
- Automated conversational bot guiding citizens through emergency reporting when web access is constrained.
- Captures location pins and media sent via Facebook Messenger and converts them into standard ALERTO incident tickets.

---

## 16. Geo-Tagged Photo and Video Uploads
- Direct client-side validation of media size, MIME type, and EXIF extraction.
- Automatic extraction and verification of EXIF GPS coordinates from submitted media.
- Secure storage in private/protected disk with signed temporary URLs.
- Server-side image optimization, watermarking with timestamp and incident ID.

---

## 17. Real-Time Notifications
- Multi-channel notification pipeline:
  - In-app WebSocket alerts with customizable audio chimes.
  - Push notifications (PWA / Web Push API).
  - SMS gateway integration for critical civil defense advisories.
  - Email notification summaries for administrative reviews.

---

## 18. User Presence Monitoring
- Heartbeat / presence ping tracking online status of Personnel and Dispatchers.
- Automatic "Away" or "Inactive" timeout after 5 minutes of inactivity.
- EOC Admin live presence overview of all on-duty personnel.

---

## 19. Admin Animations & UI Standards
- High-performance hardware-accelerated animations using GSAP (GreenSock Animation Platform).
- Layout transitions using CSS Grid and Flexbox with Tailwind CSS.
- Visual alerts: Pulsing emergency indicators, glow effects for critical incidents, animated counter numbers for live metrics.

---

## 20. Automated Testing Requirements
- Test-Driven Development (TDD) principles for core business logic.
- Minimum coverage requirements:
  - Unit tests for all Service classes, spatial calculations, and routing algorithms.
  - Feature tests for all API endpoints, Form Requests, and Policy authorization rules.
  - Database rollback using `RefreshDatabase` trait on dedicated test environment.

---

## 21. Security Requirements
- Strict Input Sanitization: Zero trust on all user-submitted parameters.
- OWASP Top 10 compliance:
  - SQL Injection prevention via Eloquent ORM parameter binding.
  - XSS prevention via Blade auto-escaping (`{{ }}`).
  - CSRF tokens required on all POST/PUT/PATCH/DELETE requests.
- Sensitive Data Protection: Never log passwords, API tokens, or identifiable medical info.
- Strict Content Security Policy (CSP) headers.

---

## 22. Production Deployment Standards
- Environment variables maintained exclusively in `.env`.
- Configuration, route, and view caching enabled in production (`config:cache`, `route:cache`, `view:cache`).
- Worker queue daemon managed via Supervisor for continuous background processing.
- HTTPS enforced with HSTS headers.
- Automated daily database backups with off-site replication.

