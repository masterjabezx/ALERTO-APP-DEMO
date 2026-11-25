# ALERTO Media Geo-Tagging & Forensic Telemetry

## 1. Ethical Geo-Tagging & Provenance Principles
In emergency disaster management, accurate geographic evidence is critical. The ALERTO system enforces strict boundaries between where a file was originally captured versus where it was uploaded:
- **Never conflate upload position with capture position.**
- **GPS permission is always optional** for ordinary conversation. Users can communicate without granting location access.
- **Coordinates must never be fabricated.**

---

## 2. GPS Sources Classification
The `media_geo_metadata.gps_source` enum tracks provenance using four defined states:

| GPS Source | Definition | Trust Level | Coordinates Origin |
| :--- | :--- | :--- | :--- |
| `exif_embedded` | Embedded directly within the original image EXIF metadata at shutter click. | Highest (Forensic) | Image EXIF header |
| `device_live_upload` | Captured via browser/device Geolocation API at time of field transmission. | Verified Field Telemetry | Device GPS sensor |
| `incident_inherited` | Explicitly selected by responder/admin to anchor attachment to the incident location. | Contextual Baseline | Incident coordinates |
| `none` | No GPS data available or location permission withheld. | Informational Only | `NULL` |

---

## 3. Database Schema (`media_geo_metadata`)
```sql
CREATE TABLE `media_geo_metadata` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `media_id` BIGINT UNSIGNED UNIQUE NOT NULL,
    `latitude` DECIMAL(10, 8) NULL,
    `longitude` DECIMAL(11, 8) NULL,
    `accuracy_meters` DECIMAL(6, 2) NULL,
    `gps_source` ENUM('exif_embedded', 'device_live_upload', 'incident_inherited', 'none') DEFAULT 'none',
    `is_inherited` TINYINT(1) DEFAULT 0,
    `camera_make` VARCHAR(128) NULL,
    `camera_model` VARCHAR(128) NULL,
    `captured_at` TIMESTAMP NULL,
    FOREIGN KEY (`media_id`) REFERENCES `media_attachments` (`id`) ON DELETE CASCADE
);
```

---

## 4. UI/UX Map Integration
Every geo-tagged message bubble in Admin, Office, and Personnel portals displays:
- Accurate GPS coordinates formatted to 6 decimal places.
- Horizontal accuracy in meters (`±5m`).
- Provenance badge (`[DEVICE LIVE UPLOAD]`, `[EXIF EMBEDDED]`, `[INCIDENT INHERITED]`).
- Interactive **"View on Map"** button linking directly to the Live Operations Map centered on the evidence coordinates (`/admin/map?lat=...&lng=...&zoom=17`).

