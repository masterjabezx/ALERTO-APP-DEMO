# Centralized Media Security & Privacy Controls

## 1. Zero-Trust Upload Validation (`MediaMetadataService`)
Never trusts client extensions alone:
1. **Extension Allowlist & Executable Blacklist:** Dynamically enforces Admin-configured allowlists (`jpeg,jpg,png,webp,mp4,webm,mov,pdf,doc,docx`) and rejects dangerous extensions (`.php`, `.phtml`, `.exe`, `.sh`, `.js`, `.html`, `.svg`, etc.).
2. **MIME Verification:** Validates declared MIME matches legitimate map per file extension.
3. **Magic Byte & Content Inspection:** Reads file binary headers to block script/executable payloads (`<?php`, `<?=`, `<script`, `MZ` PE, `\x7FELF` Linux binaries, `#!/`) and verify true format signatures (`\xFF\xD8\xFF` JPEG, `\x89PNG` PNG, `RIFF....WEBP`, MP4/MOV `ftyp`, WebM EBML, `%PDF-`, DOC/DOCX headers).
4. **Rate Limiting & Size Limits:** Upload endpoints enforce Laravel `throttle:media-upload` (30 requests/min per user) and configurable per-category size thresholds.
5. **Non-Execution & Header Hardening:** Stored on `private` disk with UUID filenames and streamed with `X-Content-Type-Options: nosniff`.
6. **Malware Scanning Readiness:** Extensible `MediaMetadataService::scanForMalware()` hook supports `clamdscan` when enabled via `media_malware_scan_enabled`.

---

## 2. Role-Scoped Access Policy (`App\Policies\MediaPolicy`)
Enforces strictly 4 account types (**No Super Admin**):
- **Admin:** Authorized to view, stream, verify, archive, and purge media across the command system.
- **Citizen:** Permitted ONLY for media linked to incidents where `reporter_id === $user->id`. Blocked (`403 Forbidden`) from other citizens' evidence, Internal Messenger attachments, and Facebook Messenger media.
- **Personnel:** Permitted for own uploads, conversations where participant, or evidence linked to incidents assigned to them / their team / active jurisdiction.
- **Office:** Permitted for own uploads, conversations where participant, or evidence linked to incidents within their office jurisdiction/assignment.

---

## 3. Residential Privacy Protection
- Sensitive EXIF/GPS locations of private citizen submissions are never publicly linked on web routes or static asset folders.
- Unavailable coordinates remain `null` and never expose home centroids falsely.

