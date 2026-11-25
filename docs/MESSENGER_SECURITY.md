# ALERTO Internal Messenger Security Architecture

## 1. Zero Trust Architecture & Multi-Tenant Isolation
The Internal Messenger handles critical emergency communications and sensitive operational information. The following security invariants are enforced:

### 1. Citizen Access Complete Prohibition
- Citizens have **zero access** to internal messenger endpoints or channels.
- Route middleware (`account.type:admin`, `account.type:office`, `account.type:personnel`) rejects citizen requests with redirects or `403 Forbidden`.
- Broadcasting channels (`routes/channels.php`) explicitly deny access to citizen accounts:
  ```php
  if ($user->isCitizen()) {
      return false;
  }
  ```

### 2. Role-Based Communication Isolation
- Cross-agency isolation: Users belonging to different offices cannot initiate unauthorized direct communication with each other unless routed through Admin or an authorized joint group.
- Group creation restriction: Only `admin` accounts can create multi-participant group channels.

### 3. Attachment Authorization & Storage Defense
- Storage Disk: Strictly `private`.
- Direct downloads inspect user participation on the message's conversation:
  ```php
  public function canDownloadAttachment(User $user, MediaAttachment $media): bool
  {
      if ($user->isCitizen()) return false;
      if ($user->isAdmin() || $media->uploader_id === $user->id) return true;
      return $media->messages()->whereHas('conversation.participants', function ($q) use ($user) {
          $q->where('user_id', $user->id);
      })->exists();
  }
  ```
- File validation: 20MB maximum size limit and strict MIME whitelist (`image/jpeg`, `image/png`, `image/webp`, `video/mp4`, `video/webm`, `application/pdf`, `.doc`, `.docx`).

### 4. Integrity Checksums & Audit Trail
- Every media attachment stores a SHA-256 checksum computed during intake.
- Every message read action generates a `message_read_receipts` record with timestamp and user reference.

