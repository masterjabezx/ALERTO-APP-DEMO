# ALERTO APP — Eloquent Model Relationships & Data Integrity Map

## 1. Overview
This reference guides the implementation of Eloquent models, foreign key cascading constraints, and relational integrity across the 56 database tables in ALERTO APP.

---

## 2. One-to-One Relationships (1:1)

| Parent Model | Child Model | Foreign Key | Eloquent Parent Method | Eloquent Child Method | On Delete Behavior |
|---|---|---|---|---|---|
| `User` | `CitizenProfile` | `user_id` | `hasOne(CitizenProfile::class)` | `belongsTo(User::class)` | `CASCADE` |
| `User` | `PersonnelProfile` | `user_id` | `hasOne(PersonnelProfile::class)`| `belongsTo(User::class)` | `CASCADE` |
| `User` | `UserPresence` | `user_id` | `hasOne(UserPresence::class)` | `belongsTo(User::class)` | `CASCADE` |
| `PersonnelProfile` | `PersonnelAvailability` | `personnel_id` | `hasOne(PersonnelAvailability::class)`| `belongsTo(PersonnelProfile::class)`| `CASCADE` |
| `Incident` | `IncidentLocation` | `incident_id` | `hasOne(IncidentLocation::class)` | `belongsTo(Incident::class)` | `CASCADE` |
| `Incident` | `Conversation` | `incident_id` | `hasOne(Conversation::class)` | `belongsTo(Incident::class)` | `SET NULL` |
| `Barangay` | `BarangayBoundary` | `barangay_id` | `hasOne(BarangayBoundary::class)` | `belongsTo(Barangay::class)` | `CASCADE` |
| `Office` | `OfficeLocation` | `office_id` | `hasOne(OfficeLocation::class)` | `belongsTo(Office::class)` | `CASCADE` |
| `MediaAttachment` | `MediaGeoMetadata` | `media_id` | `hasOne(MediaGeoMetadata::class)` | `belongsTo(MediaAttachment::class)`| `CASCADE` |

---

## 3. One-to-Many Relationships (1:N)

| Parent Model | Child Model | Foreign Key | Eloquent Parent Method | Eloquent Child Method | On Delete Behavior |
|---|---|---|---|---|---|
| `Municipality` | `Barangay` | `municipality_id` | `hasMany(Barangay::class)` | `belongsTo(Municipality::class)` | `RESTRICT` |
| `Office` | `PersonnelProfile`| `office_id` | `hasMany(PersonnelProfile::class)`| `belongsTo(Office::class)` | `RESTRICT` |
| `Office` | `ResponseTeam` | `office_id` | `hasMany(ResponseTeam::class)` | `belongsTo(Office::class)` | `RESTRICT` |
| `IncidentCategory`| `Incident` | `category_id` | `hasMany(Incident::class)` | `belongsTo(IncidentCategory::class)`| `RESTRICT` |
| `Barangay` | `Incident` | `barangay_id` | `hasMany(Incident::class)` | `belongsTo(Barangay::class)` | `RESTRICT` |
| `User` (Citizen) | `Incident` | `reporter_id` | `hasMany(Incident::class, 'reporter_id')`| `belongsTo(User::class, 'reporter_id')`| `SET NULL` |
| `Incident` | `IncidentReview` | `incident_id` | `hasMany(IncidentReview::class)`| `belongsTo(Incident::class)` | `CASCADE` |
| `Incident` | `IncidentAssignment`| `incident_id` | `hasMany(IncidentAssignment::class)`| `belongsTo(Incident::class)` | `CASCADE` |
| `Incident` | `IncidentStatusHistory`| `incident_id`| `hasMany(IncidentStatusHistory::class)`| `belongsTo(Incident::class)`| `CASCADE` |
| `Incident` | `ResponseLog` | `incident_id` | `hasMany(ResponseLog::class)` | `belongsTo(Incident::class)` | `CASCADE` |
| `Incident` | `IncidentEvidenceRecord`| `incident_id`| `hasMany(IncidentEvidenceRecord::class)`| `belongsTo(Incident::class)`| `CASCADE` |
| `Conversation` | `Message` | `conversation_id`| `hasMany(Message::class)` | `belongsTo(Conversation::class)`| `CASCADE` |
| `User` | `Notification` | `user_id` | `hasMany(Notification::class)` | `belongsTo(User::class)` | `CASCADE` |
| `Notification` | `NotificationDelivery`| `notification_id`| `hasMany(NotificationDelivery::class)`| `belongsTo(Notification::class)`| `CASCADE` |
| `FbConversation` | `FbMessage` | `fb_conversation_id`| `hasMany(FbMessage::class)` | `belongsTo(FbConversation::class)`| `CASCADE` |
| `FbConversation` | `IncidentIntakeRecord`| `fb_conversation_id`| `hasMany(IncidentIntakeRecord::class)`| `belongsTo(FbConversation::class)`| `CASCADE` |
| `PersonnelProfile`| `PersonnelLocation`| `personnel_id` | `hasMany(PersonnelLocation::class)`| `belongsTo(PersonnelProfile::class)`| `CASCADE` |

---

## 4. Many-to-Many Relationships (M:N)

| Model A | Model B | Pivot Table | Pivot Columns | Eloquent Relationship |
|---|---|---|---|---|
| `User` | `Role` | `role_user` | `user_id`, `role_id` | `belongsToMany(Role::class)` |
| `Role` | `Permission` | `permission_role` | `role_id`, `permission_id` | `belongsToMany(Permission::class)` |
| `User` | `Office` | `office_users` | `user_id`, `office_id`, `office_level`, `can_review_incidents`, `can_dispatch_units` | `belongsToMany(Office::class)->withPivot(...)` |
| `ResponseTeam` | `PersonnelProfile`| `team_memberships`| `team_id`, `personnel_id`, `role_in_team`, `assigned_at` | `belongsToMany(PersonnelProfile::class)->withPivot(...)` |
| `Conversation` | `User` | `conversation_participants`| `conversation_id`, `user_id`, `is_admin`, `last_read_message_id` | `belongsToMany(User::class)->withPivot(...)` |
| `Message` | `MediaAttachment`| `message_attachments`| `message_id`, `media_id` | `belongsToMany(MediaAttachment::class)` |
| `Message` | `User` (Receipts)| `message_read_receipts`| `message_id`, `user_id`, `read_at` | `belongsToMany(User::class)->withPivot('read_at')` |

