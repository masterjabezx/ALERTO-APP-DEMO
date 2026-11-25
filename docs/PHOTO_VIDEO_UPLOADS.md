# Photo & Video Uploads Integration Guide

## 1. Unified Multi-Portal Ingestion Pipeline

| Upload Source | User Interface & Features | Backend Integration |
| :--- | :--- | :--- |
| **Citizen Incident Reporting** | `<x-file-upload>` component in `dashboards/citizen.blade.php` with **Take Photo** (`capture="environment"`), **Record Video** (`capture="environment"`), **Select Gallery**, multi-file previews, remove-before-submit (`DataTransfer`), client size/MIME check, animated upload progress bar, and optional non-blocking GPS capture. | `SubmitIncidentRequest` → `CitizenIncidentService::submitIncident()` → `MediaManagementService::storeUploadedFile()` |
| **Personnel Internal Messenger** | Fast mobile field composer in `personnel/messages.blade.php` with camera/video capture, optional GPS toggle (never required for ordinary chat), and video playback preview. | `SendMessageRequest` → `InternalMessengerController::storeMessage()` → `InternalMessengerService::sendMessage()` → `MediaManagementService::storeUploadedFile()` |
| **Office Internal Messenger** | Agency desktop documentation composer in `office/messages.blade.php` supporting photos, videos, PDF/DOC/DOCX reports, optional GPS, and one-click "Inherit Incident GPS". | `SendMessageRequest` → `InternalMessengerService::sendMessage()` → `MediaManagementService::storeUploadedFile()` |
| **Admin Internal Messenger** | Command communication desk in `admin/messages/index.blade.php` supporting bidirectional media exchange & dispatch channels. | `SendMessageRequest` → `InternalMessengerService::sendMessage()` → `MediaManagementService::storeUploadedFile()` |
| **Facebook Messenger Intake** | Meta CDN binary attachment ingestion on webhook event receipt (`ProcessFacebookMessengerWebhookJob`). | `FacebookMessengerService::processAttachments()` → `MediaManagementService::storeRemoteBinaryContent()` |

---

## 2. Citizen Post-Submission & Evidence Lock Rules
- **Pending Review (`submitted`, `pending_review`):** Citizens may submit supplementary photo/video evidence via `POST /citizen/records/{reference}/evidence`.
- **Accepted & Operational (`accepted` onward):** `AdminIncidentService::reviewIncident()` calls `MediaManagementService::lockIncidentEvidence()`, setting `is_locked_evidence = true`.
- Attempting to replace or delete accepted evidence returns `403 Forbidden`. All original submitted files remain preserved byte-for-byte on `private` disk.

---

## 3. Asynchronous Media Processing Lifecycle (`ProcessMediaAttachmentJob`)
Every upload tracks an explicit `processing_status`:
- `uploading` → Upload initiated
- `uploaded` → Original verified and stored on `private` disk with SHA-256 hash
- `processing` → Background job running dimension/duration & derivative extraction
- `ready` → Preview/thumbnail and metadata processing complete
- `failed` → Processing exception captured in `processing_error`

> [!IMPORTANT]
> A processing failure **NEVER** deletes or modifies the original uploaded file. Admins can re-queue failed items anytime from the Governance Console (`/admin/media/{media}/retry`).

