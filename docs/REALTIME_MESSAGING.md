# ALERTO Real-Time Messaging & Broadcasting Guide

## 1. Broadcasting Infrastructure
Real-time messaging in ALERTO APP is powered by **Laravel Reverb**, **Laravel Echo**, and private authenticated channels.

## 2. Private Broadcast Channels
Configured in `routes/channels.php`:

### `conversation.{conversationId}`
```php
Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId): bool {
    if ($user->isCitizen()) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    return $conversation->participants()->where('user_id', $user->id)->exists();
});
```
- **Access Rule:** Citizens are explicitly rejected. Admins have access to all channels. Field responders and office users must be confirmed participants of the conversation.

### `user.{userId}`
```php
Broadcast::channel('user.{userId}', function (User $user, $userId): bool {
    if ($user->isCitizen()) {
        return false;
    }

    return (int) $user->id === (int) $userId;
});
```

---

## 3. Dispatched Events (`ShouldBroadcastNow`)
All messenger broadcast events implement `ShouldBroadcastNow` for sub-second emergency response synchronization without queue delay.

### 1. `InternalMessageSent`
- **Channel:** `private-conversation.{conversation_id}`
- **Payload:**
  - `id`: Message primary key
  - `conversation_id`: Associated channel ID
  - `body`: Text content
  - `message_type`: `text`, `image`, `video`, `document`
  - `sender`: `{ id, name, account_type, badge, office }`
  - `incident`: `{ id, reference_number, category }` (if incident-linked)
  - `attachments`: Array of attached media with download URLs and GPS coordinates
  - `created_at`: ISO-8601 formatted timestamp

### 2. `MessageRead`
- **Channel:** `private-conversation.{conversation_id}`
- **Payload:** `{ conversation_id, message_id, reader_id, reader_name, read_at }`
- **Purpose:** Updates double-check read receipts across client views in real-time.

### 3. `ConversationUpdated`
- **Channel:** `private-conversation.{conversation_id}`
- **Payload:** `{ conversation_id, title, type, last_message, updated_at }`
- **Purpose:** Refreshes channel list snippets, ordering, and badge counts.

