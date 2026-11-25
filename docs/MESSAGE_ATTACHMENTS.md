# ALERTO Message Attachments & Storage Implementation

## 1. Supported Media & Document Formats
The internal messenger accepts the following verified file types:
- **Images:** JPEG, PNG, WEBP (`image/jpeg`, `image/png`, `image/webp`)
- **Videos:** MP4, WEBM (`video/mp4`, `video/webm`)
- **Documents:** PDF, DOC, DOCX (`application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`)
- **Maximum File Size:** 20,480 KB (20 MB)

---

## 2. Private Storage Architecture
All message attachments are strictly kept off public directories and stored on the `private` filesystem disk:
- **Disk:** `private` (defined in `config/filesystems.php`)
- **Directory Path:** `messenger_attachments/{conversation_id}/{unique_filename}`
- **Security Checksum:** SHA-256 hash computed at upload time for integrity verification.
- **Database Tracking:** Every file creates a record in `media_attachments` with:
  - `uploader_id`
  - `disk = 'private'`
  - `file_path`
  - `file_name`
  - `mime_type`
  - `file_size_bytes`
  - `checksum_sha256`

---

## 3. Secure Streaming & Download Route
Direct file access via public URLs or symbolic links is forbidden. All downloads pass through the authenticated endpoint:
- **Route:** `GET /messenger/attachments/{id}/download` (Named: `messenger.attachments.download`)
- **Authorization Enforcement:**
  - Citizen accounts: Blocked (`403 Forbidden`).
  - Admin: Unrestricted download access.
  - Personnel / Office: Permitted only if the user is a confirmed participant in a conversation linked to the media attachment.

