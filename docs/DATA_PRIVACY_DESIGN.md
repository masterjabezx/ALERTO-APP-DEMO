# ALERTO APP — Data Privacy, Security & Spatial Governance Specification

## 1. Core Data Privacy Mandates

### Mandate 1: Strict Citizen Geolocation Privacy
> [!CAUTION]
> **ABSOLUTE PRIVACY RULE:** Under no circumstance shall citizen real-time physical GPS locations be tracked or stored continuously when their accounts are online.
> Citizen presence tracking (`user_presences`) is strictly limited to network connection state (`online`, `idle`, `offline`), IP address, and heartbeat timestamp.

- **Incident-Only Spatial Capture:** A citizen's physical latitude and longitude are recorded **strictly and exclusively** when they submit a formal emergency incident report.
- **Residential Address Security:** Residential coordinates in `citizen_addresses` represent declared home points and are accessible only to verified dispatchers during active emergencies in that specific residential area.

---

## 2. Personnel Telemetry vs. Citizen Privacy

| Dimension | Citizen Account | Personnel Account |
|---|---|---|
| **Presence Tracking** | Online / Offline status only | Online / Offline status + shift status |
| **GPS Tracking** | ❌ NEVER tracked continuously | ✅ High-frequency telemetry via `personnel_locations` |
| **Tracking Condition** | Active emergency submission only | Active duty session (`operational_status != 'off_duty'`) |
| **Data Retention** | Incident location retained with report | Location breadcrumbs pruned or aggregated after 90 days |
| **Public Visibility** | Anonymized on public safety map | Call-signs visible on Admin & Office Operations Map |

---

## 3. Cryptographic Protection & Evidence Integrity

- **Password Storage:** Hashed using Bcrypt with a minimum cost factor of 12.
- **Evidence Vault:** All photos, videos, and audio evidence are stored on a private, non-public storage disk (`storage/app/private/evidence/*`).
- **Temporary Signed URLs:** Viewing evidence requires temporary HMAC-SHA256 signed URLs expiring in 15 minutes, accessible only by authenticated users whose roles pass the `IncidentPolicy`.
- **EXIF Sanitization for Public Exports:** When media is exported for public press releases or public alerts, all embedded EXIF GPS tags are stripped programmatically, leaving the original unaltered evidence file in the secure evidence vault.
- **Audit Immutability:** `audit_logs` records cannot be updated or deleted through application interfaces (`created_at` timestamp only, no `updated_at`, no soft deletes).

