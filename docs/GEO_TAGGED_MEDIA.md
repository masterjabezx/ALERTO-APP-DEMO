# ALERTO APP — Geo-Tagged Media Evidence Architecture

## 1. Overview
The **ALERTO GEO-TAGGED MEDIA AND EVIDENCE MANAGEMENT** architecture cryptographically verifies, isolates on private storage, extracts forensic metadata from, and accurately geo-tags every photo, video, and operational document submitted across Citizen, Personnel, Office, Admin, and Facebook Messenger workflows.

---

## 2. Storage & File Specifications
- **Disk:** Explicitly isolated `private` storage disk (`storage/app/private`, never exposed publicly via `public/storage`).
- **Path Structure:** `media/{YYYY}/{MM}/{source_channel}/{uuid}.{ext}` (randomized UUID filenames; original filenames preserved only as metadata).
- **Supported Media Types:**
  - **Photographs:** `jpeg`, `jpg`, `png`, `webp` (default max 15 MB)
  - **Videos:** `mp4`, `webm`, `mov` (default max 50 MB)
  - **Documents:** `pdf`, `doc`, `docx` (default max 20 MB; internal messenger/dispatch channels)
- **Max Attachment Count:** Configurable dynamically via `system_settings` (default 5 per submission).

---

## 3. Cryptographic Checksum Integrity
Before writing any file to private storage, an immutable SHA-256 digest is calculated:
```php
$checksum = hash_file('sha256', $realPath);
```
This checksum (`media_attachments.checksum_sha256`) enables continuous chain-of-custody auditing via `MediaManagementService::verifyFileIntegrity()`.

---

## 4. Standardized Location Provenance (`location_source`)
ALERTO strictly differentiates **capture coordinates** from **upload coordinates** and **inherited incident coordinates**:

| `location_source` | Meaning & Rule |
| :--- | :--- |
| `GPS_CAPTURE` | Live GPS acquired via device hardware during camera/video capture (`is_live_capture = true`). |
| `EXIF_CAPTURE` | Genuine GPS coordinates embedded in original gallery image EXIF or MP4 `©xyz` container metadata. **Never overwritten by upload or inherited coordinates.** |
| `UPLOAD_LOCATION` | Current GPS position recorded when uploading an existing gallery item that lacked EXIF GPS (stored separately in `upload_latitude` / `upload_longitude`). |
| `INCIDENT_INHERITED_LOCATION` | Citizen or incident-linked attachment containing no independent GPS inherits the confirmed incident pin (`is_inherited = true`). |
| `MESSENGER_SHARED_LOCATION` | Native location coordinates shared explicitly via Meta Facebook Messenger pin attachment. |
| `ADMIN_CONFIRMED_LOCATION` | Explicit coordinate verification or override logged by an EOC Admin (`/admin/media/{media}/confirm-location`). |
| `LOCATION_UNAVAILABLE` | Stored cleanly with `latitude = null`, `longitude = null`. Unavailable coordinates are **never** stored or rendered as `(0, 0)`. |

---

## 5. Barangay & Coordinate Columns (`media_geo_metadata`)
- **Primary Resolved Coordinate:** `latitude`, `longitude`, `accuracy_meters`, `location_source`, `verification_status`
- **Capture vs Upload Separation:** `exif_latitude`/`exif_longitude`, `upload_latitude`/`upload_longitude`/`upload_accuracy_meters`, `inherited_latitude`/`inherited_longitude`
- **Spatial Resolution:** Automatic Hinoba-an Barangay lookup (`barangay_id`) via `GeoService::identifyBarangay()` (Ray-Casting GeoJSON polygons).
- **Timestamps:** Independent `captured_at` and `uploaded_at`.
