# Evidence Retention, Chain of Custody & Disposal Policy

## 1. Chain-of-Custody & Tamper Prevention
1. **Original Binary Preservation:** Original files submitted to ALERTO are NEVER overwritten or recompressed. Thumbnails (`media/thumbnails/...`) and previews (`media/previews/...`) are created as separate derivative files by `ProcessMediaAttachmentJob`.
2. **SHA-256 Digest Verification:** Every media item stores its ingest SHA-256 hash (`checksum_sha256`). Admins inspect live checksum verification (`integrity_verified: true|false`) in the Evidence Inspector.
3. **Evidence Locking on Acceptance:** When an incident transitions from `pending_review` to `accepted`, `MediaManagementService::lockIncidentEvidence()` sets `is_locked_evidence = true`. Citizens are prohibited (`403 Forbidden`) from deleting or overwriting official evidence unless an authorized administrative evidence correction process is conducted.

---

## 2. Non-Destructive Governance & Audited Retention Disposal
**Operational evidence can NEVER be permanently deleted via ordinary UI actions.**

1. **Formal Archival (`POST /admin/media/{media}/archive`):**
   - Requires explicit Admin role + documented archival reason (minimum 5 characters).
   - Flags `is_archived = true`, records `archived_at` and `archived_by`, and inserts an immutable `AuditLog` event (`media:archived`).
   - Binary file remains intact on `private` storage.
2. **Audited Retention Disposal (`DELETE /admin/media/{media}/purge`):**
   - Blocked if evidence is NOT formally archived first.
   - Blocked if attached to any active/unclosed incident (only permitted for `resolved`, `closed`, or `rejected` incidents).
   - Requires explicit official disposal justification (`justification` min 10 chars).
   - Performs soft-deletion (`deleted_at`) while logging immutable `AuditLog` trace (`media:retention_purged`) preserving file hash and justification.
3. **Configurable Retention Window:** Default `365` days (`system_settings.media_retention_days`), managed dynamically on the **Admin Media Governance Console** (`/admin/media`).

