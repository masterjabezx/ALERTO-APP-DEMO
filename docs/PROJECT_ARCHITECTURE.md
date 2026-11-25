# ALERTO APP — System Architecture Specification

## 1. Architectural Layers & Structure

ALERTO APP follows a strict **Layered Service-Oriented Architecture** built on Laravel 13:

```text
┌─────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                     │
│  Citizen Portal  │  Personnel App  │  Office UI  │ Admin EOC│
│  (Blade + Alpine)│ (PWA Responsive)│ (Tailwind)  │ (GSAP/Map│
└──────────────┬────────────────────────────┬─────────────────┘
               ▼                            ▼
┌──────────────────────────────┐┌─────────────────────────────┐
│      HTTP ROUTING & AUTH     ││      REAL-TIME GATEWAY      │
│  Thin Controllers & Policies ││  WebSockets / Reverb / SSE  │
└──────────────┬───────────────┘└───────────┬─────────────────┘
               ▼                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION SERVICE LAYER                │
│  IncidentService       │  RoutingService   │ DispatchService│
│  MessengerService      │  PresenceService  │ FacebookService│
│  GeofenceService       │  MediaService     │ NotificationSvc│
└──────────────┬────────────────────────────┬─────────────────┘
               ▼                            ▼
┌──────────────────────────────┐┌─────────────────────────────┐
│       DATA ACCESS LAYER      ││      ASYNCHRONOUS QUEUES    │
│  Eloquent Models & Relations ││  Database Queue Workers     │
│  Optimistic Concurrency Lock ││  EXIF Extraction / Webhooks │
└──────────────┬───────────────┘└───────────┬─────────────────┘
               ▼                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      PERSISTENCE & STORAGE                  │
│       MariaDB 10.11 (Database Engine: InnoDB, utf8mb4)      │
│       Private File Storage (Evidence Media & Audit Vault)   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Ingestion Pipelines

### Pipeline 1: Citizen Web & PWA Portal
- Zero-friction emergency submission.
- Client-side GPS coordinate retrieval via HTML5 Geolocation API.
- Direct multipart form submission with client-side image compression.

### Pipeline 2: Facebook Messenger Webhook Pipeline
- Official Meta Messenger Webhook with signature validation (`X-Hub-Signature-256`).
- Idempotent event ledger (`fb_webhook_events`) to prevent duplicate message handling.
- Conversational state machine (`incident_intake_records`) to guide callers through category, phone number, and location pin collection before incident ticket issuance.

### Pipeline 3: Admin & Dispatcher Direct Intake
- Hotline / Radio dispatch intake encoded directly by operators.
- Reverse geocoding lookup and map pin placement.

---

## 3. Real-Time Operations & Telemetry

- **Personnel Telemetry:** High-frequency GPS updates written to `personnel_locations` and broadcast over authenticated WebSocket private channels (`operations.map`).
- **Presence Monitoring:** Heartbeat pings every 60 seconds from active sessions update `user_presences`. Responders with no ping for 5 minutes automatically transition to `idle`/`offline`.
- **Citizen Privacy Guard:** Citizens report location *only* during an incident. Their real-time movements are never continuously tracked when simply browsing.

---

## 4. Evidence Integrity & Media Custody

- **EXIF Extraction:** Server-side extraction of GPS tags (`Latitude`, `Longitude`, `DateTimeOriginal`, `Make`, `Model`).
- **Cryptographic Hash:** SHA-256 checksum generated at upload to ensure chain-of-custody integrity for legal proceedings.
- **Inherited Geolocation:** When media lacks embedded GPS metadata, the system explicitly tags the coordinate source as `incident_inherited` and marks `is_inherited = true` to preserve analytical truth.

