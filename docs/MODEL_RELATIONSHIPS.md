# ALERTO APP — Eloquent Model Relationships Reference

## 1. Overview
All 51 operational models in `app/Models/` implement strict typing, fillable property definitions, date/time/coordinate casts, query scopes, and explicit relationship methods.

---

## 2. Core Model Relationship Inventory

### 1. `User`
- `roles()`: `BelongsToMany(Role::class, 'role_user')`
- `permissions()`: `BelongsToMany(Permission::class, 'permission_user')`
- `citizenProfile()`: `HasOne(CitizenProfile::class)`
- `citizenAddresses()`: `HasMany(CitizenAddress::class)`
- `personnelProfile()`: `HasOne(PersonnelProfile::class)`
- `officeUsers()`: `HasMany(OfficeUser::class)`
- `offices()`: `BelongsToMany(Office::class, 'office_users')->withPivot(...)`
- `userPresence()`: `HasOne(UserPresence::class)`
- `reportedIncidents()`: `HasMany(Incident::class, 'reporter_id')`
- `reviewedIncidents()`: `HasMany(IncidentReview::class, 'reviewed_by')`
- `assignedDispatches()`: `HasMany(IncidentAssignment::class, 'assigned_by')`
- `conversations()`: `BelongsToMany(Conversation::class, 'conversation_participants')`
- `sentMessages()`: `HasMany(Message::class, 'sender_id')`
- `notifications()`: `HasMany(Notification::class)`
- `notificationSettings()`: `HasOne(UserNotificationSetting::class)`
- `auditLogs()`: `HasMany(AuditLog::class)`

### 2. `Incident`
- `reporter()`: `BelongsTo(User::class, 'reporter_id')`
- `category()`: `BelongsTo(IncidentCategory::class, 'category_id')`
- `barangay()`: `BelongsTo(Barangay::class, 'barangay_id')`
- `location()`: `HasOne(IncidentLocation::class)`
- `reviews()`: `HasMany(IncidentReview::class)`
- `assignments()`: `HasMany(IncidentAssignment::class)`
- `activeAssignment()`: `HasOne(IncidentAssignment::class)->where('status', 'active')`
- `statusHistories()`: `HasMany(IncidentStatusHistory::class)`
- `responseLogs()`: `HasMany(ResponseLog::class)`
- `evidenceRecords()`: `HasMany(IncidentEvidenceRecord::class)`
- `conversation()`: `HasOne(Conversation::class)`

### 3. `Office`
- `officeUsers()`: `HasMany(OfficeUser::class)`
- `users()`: `BelongsToMany(User::class, 'office_users')->withPivot(...)`
- `location()`: `HasOne(OfficeLocation::class)`
- `responsibilities()`: `HasMany(OfficeResponsibility::class)`
- `personnel()`: `HasMany(PersonnelProfile::class)`
- `teams()`: `HasMany(ResponseTeam::class)`
- `assignments()`: `HasMany(IncidentAssignment::class)`

### 4. `PersonnelProfile`
- `user()`: `BelongsTo(User::class)`
- `office()`: `BelongsTo(Office::class)`
- `availability()`: `HasOne(PersonnelAvailability::class, 'personnel_id')`
- `availabilitySessions()`: `HasMany(AvailabilitySession::class, 'personnel_id')`
- `teams()`: `BelongsToMany(ResponseTeam::class, 'team_memberships', 'personnel_id', 'team_id')`
- `jurisdictions()`: `BelongsToMany(Barangay::class, 'personnel_jurisdictions', 'personnel_id', 'barangay_id')`
- `locations()`: `HasMany(PersonnelLocation::class, 'personnel_id')`
- `latestLocation()`: `HasOne(PersonnelLocation::class, 'personnel_id')->latestOfMany('recorded_at')`
- `assignments()`: `HasMany(IncidentAssignment::class, 'personnel_id')`
- `responseLogs()`: `HasMany(ResponseLog::class, 'personnel_id')`

### 5. `Barangay`
- `municipality()`: `BelongsTo(Municipality::class)`
- `boundary()`: `HasOne(BarangayBoundary::class)`
- `incidents()`: `HasMany(Incident::class)`
- `citizenAddresses()`: `HasMany(CitizenAddress::class)`

### 6. `Conversation` & `Message`
- `Conversation->incident()`: `BelongsTo(Incident::class)`
- `Conversation->creator()`: `BelongsTo(User::class, 'created_by')`
- `Conversation->users()`: `BelongsToMany(User::class, 'conversation_participants')`
- `Conversation->messages()`: `HasMany(Message::class)`
- `Conversation->latestMessage()`: `HasOne(Message::class)->latestOfMany()`
- `Message->conversation()`: `BelongsTo(Conversation::class)`
- `Message->sender()`: `BelongsTo(User::class, 'sender_id')`
- `Message->attachments()`: `BelongsToMany(MediaAttachment::class, 'message_attachments', 'message_id', 'media_id')`
- `Message->readReceipts()`: `HasMany(MessageReadReceipt::class)`

### 7. `MediaAttachment`
- `uploader()`: `BelongsTo(User::class, 'uploader_id')`
- `geoMetadata()`: `HasOne(MediaGeoMetadata::class, 'media_id')`
- `evidenceRecords()`: `HasMany(IncidentEvidenceRecord::class, 'media_id')`
- `messages()`: `BelongsToMany(Message::class, 'message_attachments', 'media_id', 'message_id')`

