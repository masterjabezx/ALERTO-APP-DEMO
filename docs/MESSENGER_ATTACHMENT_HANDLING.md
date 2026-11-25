# Facebook Messenger Media & Attachment Handling

## 1. Attachment Processing Lifecycle

When citizens send photos or videos of accidents, flooding, or fallen trees via Facebook Messenger:

```
[Meta Messenger CDN Attachment URL]
                │
                ▼
[FacebookMessengerService::downloadAndStoreAttachment]
   ├── Performs secure HTTP GET stream to Meta CDN
   ├── Validates file size (Max 20MB for images, 50MB for video)
   ├── Determines MIME type (image/jpeg, image/png, video/mp4, video/quicktime)
   ├── Computes SHA-256 integrity checksum
   └── Writes file securely to Laravel 'private' storage disk
                │
                ▼
[media_attachments Table]
   ├── disk: 'private'
   ├── file_path: 'attachments/fb_{conversation_id}_{timestamp}_{rand}.{ext}'
   ├── file_hash: sha256 checksum
   └── linked to fb_message_attachments pivot
```

---

## 2. Storage Defense & Zero Public Exposure
- **Private Disk Isolation:** Attachments are NEVER saved in `public/` or `storage/app/public`.
- **Authorized Streaming:** All access to Messenger media requires an active Admin session via authenticated route `GET /admin/communications/facebook-messenger/attachments/{id}`.
- **Conversion Handoff:** When an intake is converted to a formal incident, existing `media_attachments` records are linked directly to `incident_media` without duplicating physical files on disk, saving storage and maintaining SHA-256 integrity chains.

