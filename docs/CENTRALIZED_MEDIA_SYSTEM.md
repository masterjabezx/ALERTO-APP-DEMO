# ALERTO APP Centralized Media & Evidence Management System

## 1. Overview & System Name
**Official System Name:** **ALERTO GEO-TAGGED MEDIA AND EVIDENCE MANAGEMENT**

This system provides one unified, secure, audited ingestion, storage, geo-tagging, processing, and retention pipeline across ALL portals and external communication adapters:
1. **Citizen Incident Reporting Portal** (`source_channel = citizen_portal`)
2. **Personnel Internal Messenger** (`source_channel = internal_messenger_personnel`)
3. **Office Internal Messenger** (`source_channel = internal_messenger_office`)
4. **Admin Internal Messenger** (`source_channel = internal_messenger_admin`)
5. **Facebook Messenger Incident Intake** (`source_channel = facebook_messenger`)

Zero duplicated portal upload services exist. Every upload creates a canonical record in `media_attachments`, its companion geo-tag record in `media_geo_metadata`, optional operational linkage via `incident_evidence_records` and polymorphic `attachable` (`Incident`, `Message`, `FbMessage`), and an immutable `audit_logs` entry.

---

## 2. Core Service Architecture

| Service / Component | Responsibility |
| :--- | :--- |
| `App\Services\MediaManagementService` | Unified ingress (`storeUploadedFile`, `storeRemoteBinaryContent`), SHA-256 checksum computation, randomized storage naming on `private` disk, incident evidence linking & locking, analytics queries, and audited archival/purging. |
| `App\Services\MediaMetadataService` | Dynamic policy retrieval from `system_settings`, strict security validation (extension, MIME, magic byte signatures, script/binary header blocking), pure-PHP + native EXIF JPEG GPS extraction, MP4/MOV ISO-6709 & `mvhd` atom inspection. |
| `App\Services\MediaGeotagService` | Hierarchical location source precedence engine, capture vs upload vs incident-inherited separation, Hinoba-an Barangay spatial matching via `GeoService`. |
| `App\Jobs\ProcessMediaAttachmentJob` | Queued background thumbnail & preview generation, image/video dimensions & duration extraction, safe failure marking (`processing_status = failed`) while preserving original binary evidence. |
| `App\Policies\MediaPolicy` | Role-scoped access matrix enforcing strictly 4 account types (`citizen`, `personnel`, `office`, `admin`; no Super Admin). |

---

## 3. Supported Media Formats & Configurable Restrictions

Admin controls all upload policies in real time via **Admin → Media & Evidence Management** (`/admin/media`):

| Setting Key (`system_settings`) | Default | Purpose |
| :--- | :--- | :--- |
| `media_max_photo_size_mb` | `15` MB | Max size for `jpeg`, `jpg`, `png`, `webp` |
| `media_max_video_size_mb` | `50` MB | Max size for `mp4`, `webm`, `mov` |
| `media_max_document_size_mb` | `20` MB | Max size for `pdf`, `doc`, `docx` |
| `max_upload_count` | `5` | Max attachments per report / message batch |
| `media_retention_days` | `365` | Archival retention threshold before disposal review |
| `media_auto_generate_thumbnails` | `true` | Dispatch queued thumbnail/preview generation |
| `media_malware_scan_enabled` | `false` | Optional ClamAV external scanner hook |

---

## 4. Storage Architecture
- **Disk:** Explicitly isolated Laravel `private` disk (`storage/app/private` in [config/filesystems.php](file:///c:/Users/ACER%20NITRO/Desktop/JABEZ%20DEV%20FILES/WEB/ALERTO%20APP/config/filesystems.php)), never publicly symlinked under `public/storage`.
- **Naming Strategy:** UUID randomized stored filename (`{uuid}.{ext}`), nested under `media/YYYY/MM/{source_channel}/`, while original filenames remain searchable metadata in `file_name`.
- **Access Routes:**
  - `GET /media/{media}/stream` (inline view for authorized preview)
  - `GET /media/{media}/thumbnail` (thumbnail preview)
  - `GET /media/{media}/download` (original file download with `X-Content-Type-Options: nosniff`)

---

## 5. Remaining External Host Dependencies
1. **PHP Extensions (`ext-exif`, `ext-gd`):** While pure-PHP JPEG APP1 EXIF GPS parsing, PNG/JPEG dimension detection, and MP4/MOV atom inspection already work natively without extra extensions, enabling `ext-gd` on PHP enables automatic server-side JPEG thumbnail and preview resizing in `ProcessMediaAttachmentJob`.
2. **FFmpeg / FFprobe Binary:** Installing `ffmpeg` & `ffprobe` on the host PATH enables full video poster frame thumbnail generation and codec extraction across WebM/MOV/H.264 streams. Without FFmpeg, ALERTO gracefully preserves original MP4/MOV/WebM videos and parses MP4 `mvhd`/`©xyz` boxes natively without marking failures.
3. **ClamAV (`clamdscan`):** Required only if `media_malware_scan_enabled` is toggled on in production.

