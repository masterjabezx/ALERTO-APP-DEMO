# ALERTO APP — Comprehensive Database Schema Specification

## 1. Architectural Principles & Conventions
- **Database Engine:** MariaDB 10.11+ / InnoDB
- **Default Character Set & Collation:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Primary Keys:** Unsigned Big Integer (`id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`)
- **Foreign Keys:** Explicit foreign key constraints with indexed columns and appropriate cascade behaviors (`RESTRICT` for critical audit logs/incidents, `CASCADE` for child associations like message attachments or read receipts).
- **Geographic Coordinates:** `decimal(10, 8)` for latitude (-90 to +90) and `decimal(11, 8)` for longitude (-180 to +180), paired with optional MariaDB `POINT` spatial indexes for spatial queries.
- **Strict Account Types (4 Only):** `citizen`, `personnel`, `office`, `admin`. No Super Admin.
- **Timestamps:** Standard `created_at` and `updated_at` (nullable TIMESTAMP), with `deleted_at` (SoftDeletes) for operational integrity.

---

## 2. Table Inventory by Domain Group

### Group A: User and Authentication
1. `users` — Core authentication and account identity.
2. `roles` — Master roles (strictly: `citizen`, `personnel`, `office`, `admin`).
3. `permissions` — Granular action privileges.
4. `role_user` — User role assignment pivot.
5. `permission_role` — Role permission matrix mapping.
6. `permission_user` — Direct user permission overrides.
7. `sessions` — Laravel HTTP sessions with device metadata.
8. `user_presences` — Real-time online/offline presence tracking and heartbeat.
9. `audit_logs` — System-wide security, mutation, and access audit log.

### Group B: Citizen Management
10. `citizen_profiles` — Extended citizen information (valid IDs, emergency contacts).
11. `citizen_addresses` — Registered residential/permanent locations (NOT real-time tracking).
12. `contact_verifications` — SMS/OTP/Email contact verification logs.

### Group C: Personnel Management
13. `personnel_profiles` — Responder credentials, badge numbers, designations.
14. `personnel_availabilities` — Live operational readiness status (Available, Dispatched, On Scene, Busy, Off Duty).
15. `availability_sessions` — Historical shift/duty start and end logs.
16. `response_teams` — Field operational squads (e.g., Quick Response Alpha, Medic 1).
17. `team_memberships` — Personnel to team assignments.
18. `personnel_jurisdictions` — Operational zones / assigned barangays.
19. `personnel_locations` — Real-time telemetry/GPS breadcrumb updates of active responders.

### Group D: Office Management
20. `offices` — Station and department registry (PNP, BFP, CDRRMO, RHU).
21. `office_users` — Association of users with offices, specifying office level (`office_admin` vs `office_staff`).
22. `office_locations` — Physical headquarters/station coordinates and service radius.
23. `office_responsibilities` — Incident categories assigned to specific offices.

### Group E: Geographic Management
24. `municipalities` — Municipality/City master reference.
25. `barangays` — Barangay administrative boundaries and centroids.
26. `barangay_boundaries` — GeoJSON polygon coordinates for spatial boundary checking.

### Group F: Incident Management
27. `incident_categories` — Incident classifications (Fire, Medical, Crime, Rescue, Hazard).
28. `incidents` — Master incident records with unique reference numbers and lifecycle status.
29. `incident_locations` — Precise incident coordinates, reverse-geocoded address, and landmark.
30. `incident_reviews` — Intake evaluation, acceptance, or rejection records with reviewer notes.
31. `incident_assignments` — Dispatch orders assigning personnel/teams/offices with concurrency locking.
32. `incident_routing_rules` — Deterministic automatic routing matrices based on category, priority, and barangay.
33. `incident_status_histories` — Immutable audit log of every lifecycle state transition.
34. `response_logs` — Field milestone timestamp records (Acknowledged, On the Way, On Scene, Patient Loaded, Clear).

### Group G: Media and Evidence
35. `media_attachments` — Uploaded media files (photos, videos, audio, documents).
36. `media_geo_metadata` — Extracted EXIF GPS coordinates, accuracy, GPS source, and capture timestamps.
37. `incident_evidence_records` — Evidence chain-of-custody tagging linking media to incident milestones.

### Group H: Internal Messenger
38. `conversations` — Direct, group, and incident-linked chat threads.
39. `conversation_participants` — Thread membership, mute status, and role.
40. `messages` — Chat messages with text, message types, and soft-delete capabilities.
41. `message_attachments` — Links between media attachments and chat messages.
42. `message_read_receipts` — Per-user read acknowledgment timestamps.
43. `message_notifications` — Push/in-app notification delivery records for unread messages.

### Group I: Facebook Messenger
44. `fb_integration_settings` — Page access tokens, webhook secrets, and bot parameters.
45. `fb_conversations` — Threads linked to Facebook Page PSIDs (Page-Scoped IDs).
46. `fb_messages` — Raw and parsed Facebook Messenger messages.
47. `fb_attachments` — Files and media downloaded/proxied from Meta CDN.
48. `fb_webhook_events` — Idempotent webhook event ledger preventing duplicate event execution.
49. `incident_intake_records` — Staged conversational reporting state tracking contact & location collection.

### Group J: Notification System
50. `notifications` — System-generated notifications (WebSocket, in-app, push, SMS).
51. `notification_deliveries` — Multi-channel dispatch records (Channel, status, error code, delivery time).
52. `user_notification_settings` — Granular notification preferences per account.

### Group K: System Management
53. `system_settings` — Key-value platform configurations (maintenance mode, operational hours, thresholds).
54. `emergency_hotlines` — Public directory of municipal emergency contacts.
55. `system_backup_records` — Automated and manual database backup registry.
56. `activity_logs` — High-level administrative operations log.

---

## 3. Detailed Table Schema Definitions

### Group A: User and Authentication

#### `users`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Unique user identifier |
| `name` | VARCHAR(255) | NOT NULL | User's full name |
| `email` | VARCHAR(255) | NULL, UNIQUE | User email (optional for citizen phone-only) |
| `phone_number` | VARCHAR(32) | NOT NULL, UNIQUE, INDEX | Primary mobile contact number |
| `phone_verified_at` | TIMESTAMP | NULL | Phone verification timestamp |
| `email_verified_at` | TIMESTAMP | NULL | Email verification timestamp |
| `password` | VARCHAR(255) | NOT NULL | Bcrypt hash |
| `account_type` | ENUM | NOT NULL, INDEX | `'citizen'`, `'personnel'`, `'office'`, `'admin'` |
| `is_active` | BOOLEAN | NOT NULL DEFAULT TRUE | Account state |
| `two_factor_secret` | TEXT | NULL | 2FA secret |
| `remember_token` | VARCHAR(100) | NULL | Session token |
| `created_at` | TIMESTAMP | NULL | Creation timestamp |
| `updated_at` | TIMESTAMP | NULL | Last update timestamp |
| `deleted_at` | TIMESTAMP | NULL | Soft delete timestamp |

#### `roles`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Role identifier |
| `name` | VARCHAR(64) | NOT NULL, UNIQUE | Machine slug (`admin`, `office`, `personnel`, `citizen`) |
| `display_name` | VARCHAR(128) | NOT NULL | Human-readable title |
| `description` | TEXT | NULL | Role scope explanation |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `permissions`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | Permission identifier |
| `name` | VARCHAR(128) | NOT NULL, UNIQUE | Permission slug (e.g. `incident:review`, `incident:dispatch`) |
| `group_name` | VARCHAR(64) | NOT NULL, INDEX | Categorical grouping |
| `description` | TEXT | NULL | |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `role_user`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE | |
| `role_id` | BIGINT UNSIGNED | NOT NULL, FK -> `roles.id` ON DELETE CASCADE | |
| PRIMARY KEY | `(user_id, role_id)` | | Composite PK |

#### `user_presences`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `users.id` ON DELETE CASCADE | |
| `status` | ENUM | NOT NULL DEFAULT `'offline'`, INDEX | `'online'`, `'idle'`, `'offline'` |
| `current_device` | VARCHAR(128) | NULL | User-agent summary |
| `ip_address` | VARCHAR(45) | NULL | Connection IP |
| `last_heartbeat_at` | TIMESTAMP | NOT NULL, INDEX | Last ping timestamp |
| `updated_at` | TIMESTAMP | NULL | |

#### `audit_logs`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NULL, FK -> `users.id` ON DELETE SET NULL, INDEX | Performing user |
| `event_type` | VARCHAR(64) | NOT NULL, INDEX | `login`, `incident_reviewed`, `dispatch_created`, etc. |
| `auditable_type` | VARCHAR(128) | NOT NULL, INDEX | Target model |
| `auditable_id` | BIGINT UNSIGNED | NOT NULL, INDEX | Target model ID |
| `old_values` | JSON | NULL | State prior to modification |
| `new_values` | JSON | NULL | State after modification |
| `ip_address` | VARCHAR(45) | NULL | |
| `user_agent` | TEXT | NULL | |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | |

---

### Group B: Citizen Management

#### `citizen_profiles`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `users.id` ON DELETE CASCADE | |
| `first_name` | VARCHAR(128) | NOT NULL | |
| `middle_name` | VARCHAR(128) | NULL | |
| `last_name` | VARCHAR(128) | NOT NULL | |
| `suffix` | VARCHAR(32) | NULL | |
| `birth_date` | DATE | NULL | |
| `gender` | VARCHAR(32) | NULL | |
| `blood_type` | VARCHAR(8) | NULL | Optional emergency medical data |
| `emergency_contact_name`| VARCHAR(255) | NULL | Primary emergency next-of-kin |
| `emergency_contact_phone`| VARCHAR(32) | NULL | Primary emergency phone |
| `verified_citizen` | BOOLEAN | NOT NULL DEFAULT FALSE | Identity verification status |
| `id_type` | VARCHAR(64) | NULL | Government ID presented |
| `id_number_hash` | VARCHAR(255) | NULL | Hashed ID number |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `citizen_addresses`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE | |
| `barangay_id` | BIGINT UNSIGNED | NOT NULL, FK -> `barangays.id` | Resident barangay |
| `street_address` | VARCHAR(255) | NOT NULL | Street / House No. / Purok / Sitio |
| `latitude` | DECIMAL(10, 8) | NULL | Fixed residential location latitude |
| `longitude` | DECIMAL(11, 8) | NULL | Fixed residential location longitude |
| `is_primary` | BOOLEAN | NOT NULL DEFAULT TRUE | |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

---

### Group C: Personnel Management

#### `personnel_profiles`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `users.id` ON DELETE CASCADE | |
| `office_id` | BIGINT UNSIGNED | NOT NULL, FK -> `offices.id` | Primary department affiliation |
| `badge_number` | VARCHAR(64) | NOT NULL, UNIQUE, INDEX | Service/Badge number |
| `rank_designation` | VARCHAR(128) | NOT NULL | Officer, Medic, Fire Officer I, etc. |
| `specialization` | VARCHAR(128) | NULL | Hazmat, EMT, Search & Rescue, Traffic |
| `is_team_leader` | BOOLEAN | NOT NULL DEFAULT FALSE | |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `personnel_availabilities`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `personnel_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `personnel_profiles.id` ON DELETE CASCADE | |
| `operational_status` | ENUM | NOT NULL DEFAULT `'off_duty'`, INDEX | `'available'`, `'dispatched'`, `'on_scene'`, `'busy'`, `'off_duty'` |
| `status_reason` | VARCHAR(255) | NULL | Clarification (e.g. "Meal Break", "Vehicle Refuel") |
| `status_updated_at` | TIMESTAMP | NOT NULL, INDEX | |

#### `response_teams`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `office_id` | BIGINT UNSIGNED | NOT NULL, FK -> `offices.id` | Department owner |
| `team_name` | VARCHAR(128) | NOT NULL | e.g. "Alpha Quick Response Squad" |
| `call_sign` | VARCHAR(64) | NOT NULL, UNIQUE | Tactical radio call sign (e.g. "RESCUE-01") |
| `team_type` | VARCHAR(64) | NOT NULL | `medical`, `police`, `fire`, `disaster` |
| `is_active` | BOOLEAN | NOT NULL DEFAULT TRUE | |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `personnel_locations` (High-Volume Live Operations Table)
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `personnel_id` | BIGINT UNSIGNED | NOT NULL, FK -> `personnel_profiles.id` ON DELETE CASCADE, INDEX | |
| `latitude` | DECIMAL(10, 8) | NOT NULL | |
| `longitude` | DECIMAL(11, 8) | NOT NULL | |
| `heading` | DECIMAL(5, 2) | NULL | Compass bearing in degrees (0-360) |
| `speed` | DECIMAL(6, 2) | NULL | Speed in km/h |
| `accuracy` | DECIMAL(6, 2) | NULL | GPS accuracy radius in meters |
| `recorded_at` | TIMESTAMP | NOT NULL, INDEX | Mobile GPS device timestamp |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | Server ingestion timestamp |

---

### Group D: Office Management

#### `offices`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(128) | NOT NULL | Official name (e.g., "City Police Station") |
| `code` | VARCHAR(32) | NOT NULL, UNIQUE, INDEX | Code slug (e.g., `PNP_CENTRAL`, `BFP_SUB1`) |
| `department_type` | ENUM | NOT NULL, INDEX | `'police'`, `'fire'`, `'medical'`, `'disaster'`, `'traffic'` |
| `contact_phone` | VARCHAR(32) | NOT NULL | Station hotline |
| `is_active` | BOOLEAN | NOT NULL DEFAULT TRUE | |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |

#### `office_users`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `office_id` | BIGINT UNSIGNED | NOT NULL, FK -> `offices.id` ON DELETE CASCADE | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE | |
| `office_level` | ENUM | NOT NULL DEFAULT `'office_staff'`, INDEX | `'office_admin'`, `'office_staff'` |
| `can_review_incidents` | BOOLEAN | NOT NULL DEFAULT FALSE | Specific permission flag |
| `can_dispatch_units` | BOOLEAN | NOT NULL DEFAULT FALSE | Specific permission flag |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| UNIQUE KEY | `(office_id, user_id)` | | One role per office |

#### `office_locations`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `office_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `offices.id` ON DELETE CASCADE | |
| `barangay_id` | BIGINT UNSIGNED | NOT NULL, FK -> `barangays.id` | |
| `street_address` | VARCHAR(255) | NOT NULL | |
| `latitude` | DECIMAL(10, 8) | NOT NULL | Station latitude |
| `longitude` | DECIMAL(11, 8) | NOT NULL | Station longitude |
| `service_radius_meters` | INT UNSIGNED | NOT NULL DEFAULT 5000 | Primary radius of response |

---

### Group E: Geographic Management

#### `municipalities`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(128) | NOT NULL | Municipality / City Name |
| `province` | VARCHAR(128) | NOT NULL | Province Name |
| `region` | VARCHAR(64) | NOT NULL | Administrative Region |
| `postal_code` | VARCHAR(16) | NOT NULL | |
| `center_latitude` | DECIMAL(10, 8) | NOT NULL | Map center default |
| `center_longitude` | DECIMAL(11, 8) | NOT NULL | Map center default |

#### `barangays`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `municipality_id` | BIGINT UNSIGNED | NOT NULL, FK -> `municipalities.id` | |
| `name` | VARCHAR(128) | NOT NULL, INDEX | Barangay Name |
| `contact_number` | VARCHAR(32) | NULL | Barangay Hall phone |
| `center_latitude` | DECIMAL(10, 8) | NOT NULL | Centroid latitude |
| `center_longitude` | DECIMAL(11, 8) | NOT NULL | Centroid longitude |

#### `barangay_boundaries`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `barangay_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `barangays.id` ON DELETE CASCADE | |
| `geojson_boundary` | LONGTEXT | NOT NULL | Valid GeoJSON polygon string |
| `bounding_min_lat` | DECIMAL(10, 8) | NOT NULL, INDEX | Bounding box minimum latitude |
| `bounding_max_lat` | DECIMAL(10, 8) | NOT NULL, INDEX | Bounding box maximum latitude |
| `bounding_min_lng` | DECIMAL(11, 8) | NOT NULL, INDEX | Bounding box minimum longitude |
| `bounding_max_lng` | DECIMAL(11, 8) | NOT NULL, INDEX | Bounding box maximum longitude |

---

### Group F: Incident Management

#### `incidents`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `reference_number` | VARCHAR(32) | NOT NULL, UNIQUE, INDEX | Format: `ALT-YYYYMMDD-XXXXX` |
| `reporter_id` | BIGINT UNSIGNED | NULL, FK -> `users.id` ON DELETE SET NULL, INDEX | Registered citizen (null if FB/anonymous) |
| `source_channel` | ENUM | NOT NULL, INDEX | `'citizen_portal'`, `'facebook_messenger'`, `'admin_encoded'` |
| `category_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incident_categories.id`, INDEX | Incident category |
| `priority` | ENUM | NOT NULL DEFAULT `'medium'`, INDEX | `'critical'`, `'high'`, `'medium'`, `'low'` |
| `status` | ENUM | NOT NULL DEFAULT `'submitted'`, INDEX | `'submitted'`, `'pending_review'`, `'accepted'`, `'dispatched'`, `'acknowledged'`, `'on_the_way'`, `'on_scene'`, `'in_progress'`, `'resolved'`, `'closed'`, `'rejected'` |
| `description` | TEXT | NOT NULL | Incident narrative details |
| `caller_name` | VARCHAR(128) | NOT NULL | Contact name reported |
| `caller_phone` | VARCHAR(32) | NOT NULL, INDEX | Contact phone number |
| `barangay_id` | BIGINT UNSIGNED | NOT NULL, FK -> `barangays.id`, INDEX | Location barangay |
| `submitted_at` | TIMESTAMP | NOT NULL, INDEX | Ingestion timestamp |
| `accepted_at` | TIMESTAMP | NULL | Acceptance timestamp |
| `resolved_at` | TIMESTAMP | NULL | Resolution timestamp |
| `closed_at` | TIMESTAMP | NULL | Final sign-off timestamp |
| `version_lock` | INT UNSIGNED | NOT NULL DEFAULT 1 | Optimistic concurrency locking |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL | |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

#### `incident_locations`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `incidents.id` ON DELETE CASCADE | |
| `latitude` | DECIMAL(10, 8) | NOT NULL, INDEX | Incident coordinates |
| `longitude` | DECIMAL(11, 8) | NOT NULL, INDEX | Incident coordinates |
| `accuracy` | DECIMAL(6, 2) | NULL | GPS accuracy in meters |
| `street_address` | VARCHAR(255) | NULL | Reverse-geocoded or citizen-provided address |
| `landmark` | VARCHAR(255) | NULL | Prominent nearby reference point |
| `location_source` | ENUM | NOT NULL | `'gps_device'`, `'map_picker'`, `'fb_shared_location'`, `'admin_entered'` |

#### `incident_reviews`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incidents.id` ON DELETE CASCADE, INDEX | |
| `reviewed_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` | Admin or Office Admin reviewer |
| `decision` | ENUM | NOT NULL, INDEX | `'accepted'`, `'rejected'` |
| `rejection_reason` | VARCHAR(255) | NULL | Required if rejected (e.g. "Prank", "Duplicate") |
| `review_notes` | TEXT | NULL | Triage assessment notes |
| `reviewed_at` | TIMESTAMP | NOT NULL, INDEX | |

#### `incident_assignments`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incidents.id` ON DELETE CASCADE, INDEX | |
| `office_id` | BIGINT UNSIGNED | NOT NULL, FK -> `offices.id`, INDEX | Responsible Office |
| `team_id` | BIGINT UNSIGNED | NULL, FK -> `response_teams.id`, INDEX | Assigned Team |
| `personnel_id` | BIGINT UNSIGNED | NULL, FK -> `personnel_profiles.id`, INDEX | Assigned Individual Responder |
| `assigned_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` | Dispatcher (Admin or Office Admin) |
| `assignment_type` | ENUM | NOT NULL DEFAULT `'manual'` | `'automatic_rule'`, `'manual'` |
| `status` | ENUM | NOT NULL DEFAULT `'active'`, INDEX | `'active'`, `'reassigned'`, `'completed'`, `'cancelled'` |
| `assigned_at` | TIMESTAMP | NOT NULL, INDEX | |
| `acknowledged_at`| TIMESTAMP | NULL | Responder confirmation |
| `notes` | TEXT | NULL | Dispatch instructions |

#### `incident_routing_rules`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `category_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incident_categories.id` | |
| `barangay_id` | BIGINT UNSIGNED | NULL, FK -> `barangays.id` | Null applies to entire municipality |
| `priority` | ENUM | NULL | Priority trigger filter |
| `target_office_id` | BIGINT UNSIGNED | NOT NULL, FK -> `offices.id` | Primary routed department |
| `fallback_office_id`| BIGINT UNSIGNED | NULL, FK -> `offices.id` | Secondary department if unavailable |
| `auto_dispatch` | BOOLEAN | NOT NULL DEFAULT FALSE | True = auto-assign, False = recommend |
| `is_active` | BOOLEAN | NOT NULL DEFAULT TRUE, INDEX | |

#### `incident_status_histories`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incidents.id` ON DELETE CASCADE, INDEX | |
| `previous_status` | VARCHAR(32) | NOT NULL | State transitioned from |
| `new_status` | VARCHAR(32) | NOT NULL, INDEX | State transitioned to |
| `changed_by` | BIGINT UNSIGNED | NULL, FK -> `users.id` ON DELETE SET NULL | User who caused transition |
| `change_reason` | VARCHAR(255) | NULL | Justification for status movement |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | Transition timestamp |

#### `response_logs`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incidents.id` ON DELETE CASCADE, INDEX | |
| `assignment_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incident_assignments.id` ON DELETE CASCADE | |
| `personnel_id` | BIGINT UNSIGNED | NOT NULL, FK -> `personnel_profiles.id` | Responding unit |
| `milestone` | ENUM | NOT NULL, INDEX | `'acknowledged'`, `'on_the_way'`, `'on_scene'`, `'in_progress'`, `'cleared'` |
| `latitude` | DECIMAL(10, 8) | NULL | Location at time of milestone click |
| `longitude` | DECIMAL(11, 8) | NULL | Location at time of milestone click |
| `notes` | TEXT | NULL | Field log remarks |
| `recorded_at` | TIMESTAMP | NOT NULL, INDEX | |

---

### Group G: Media and Evidence

#### `media_attachments`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `uploader_id` | BIGINT UNSIGNED | NULL, FK -> `users.id` ON DELETE SET NULL, INDEX | User who submitted media |
| `disk` | VARCHAR(32) | NOT NULL DEFAULT `'private'` | Storage disk (`private`, `public`, `s3`) |
| `file_path` | VARCHAR(512) | NOT NULL | Relative path in storage |
| `file_name` | VARCHAR(255) | NOT NULL | Original sanitized filename |
| `mime_type` | VARCHAR(128) | NOT NULL, INDEX | `image/jpeg`, `video/mp4`, etc. |
| `file_size_bytes`| BIGINT UNSIGNED | NOT NULL | File size |
| `checksum_sha256`| CHAR(64) | NOT NULL, INDEX | File integrity hash |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | Upload timestamp |

#### `media_geo_metadata`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `media_id` | BIGINT UNSIGNED | NOT NULL, UNIQUE, FK -> `media_attachments.id` ON DELETE CASCADE | |
| `latitude` | DECIMAL(10, 8) | NULL, INDEX | Exact capture latitude |
| `longitude` | DECIMAL(11, 8) | NULL, INDEX | Exact capture longitude |
| `accuracy_meters` | DECIMAL(6, 2) | NULL | GPS accuracy |
| `gps_source` | ENUM | NOT NULL | `'exif_embedded'`, `'device_live_upload'`, `'incident_inherited'`, `'none'` |
| `is_inherited` | BOOLEAN | NOT NULL DEFAULT FALSE | True if inherited from incident coords |
| `camera_make` | VARCHAR(128) | NULL | EXIF hardware data |
| `camera_model` | VARCHAR(128) | NULL | EXIF hardware data |
| `captured_at` | TIMESTAMP | NULL, INDEX | Original EXIF capture timestamp |

#### `incident_evidence_records`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `incident_id` | BIGINT UNSIGNED | NOT NULL, FK -> `incidents.id` ON DELETE CASCADE, INDEX | |
| `media_id` | BIGINT UNSIGNED | NOT NULL, FK -> `media_attachments.id` ON DELETE CASCADE | |
| `evidence_type` | ENUM | NOT NULL | `'citizen_submission'`, `'responder_on_scene'`, `'damage_assessment'`, `'resolution_proof'` |
| `caption` | VARCHAR(255) | NULL | |
| `created_at` | TIMESTAMP | NOT NULL | |

---

### Group H: Internal Messenger

#### `conversations`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `type` | ENUM | NOT NULL, INDEX | `'direct'`, `'group'`, `'incident'` |
| `incident_id` | BIGINT UNSIGNED | NULL, UNIQUE, FK -> `incidents.id` ON DELETE CASCADE | One thread per incident |
| `title` | VARCHAR(255) | NULL | Group/Incident title |
| `created_by` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` | Conversation initiator |
| `created_at` | TIMESTAMP | NULL | |
| `updated_at` | TIMESTAMP | NULL, INDEX | Sorted by latest message activity |

#### `conversation_participants`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `conversation_id` | BIGINT UNSIGNED | NOT NULL, FK -> `conversations.id` ON DELETE CASCADE | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE | |
| `is_admin` | BOOLEAN | NOT NULL DEFAULT FALSE | Can add/remove members |
| `joined_at` | TIMESTAMP | NOT NULL | |
| `last_read_message_id`| BIGINT UNSIGNED | NULL | Pointer to last read message |
| UNIQUE KEY | `(conversation_id, user_id)` | | Prevent duplicate membership |

#### `messages`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `conversation_id` | BIGINT UNSIGNED | NOT NULL, FK -> `conversations.id` ON DELETE CASCADE, INDEX | |
| `sender_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id`, INDEX | |
| `body` | TEXT | NULL | Nullable if message is purely media |
| `message_type` | ENUM | NOT NULL DEFAULT `'text'` | `'text'`, `'media'`, `'system_event'`, `'location'` |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

#### `message_attachments`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `message_id` | BIGINT UNSIGNED | NOT NULL, FK -> `messages.id` ON DELETE CASCADE | |
| `media_id` | BIGINT UNSIGNED | NOT NULL, FK -> `media_attachments.id` ON DELETE CASCADE | |

#### `message_read_receipts`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `message_id` | BIGINT UNSIGNED | NOT NULL, FK -> `messages.id` ON DELETE CASCADE | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE | |
| `read_at` | TIMESTAMP | NOT NULL | |
| UNIQUE KEY | `(message_id, user_id)` | | |

---

### Group I: Facebook Messenger

#### `fb_integration_settings`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `page_id` | VARCHAR(128) | NOT NULL, UNIQUE | Meta Page ID |
| `page_name` | VARCHAR(255) | NOT NULL | Page Title |
| `verify_token` | VARCHAR(255) | NOT NULL | Webhook handshake token |
| `is_active` | BOOLEAN | NOT NULL DEFAULT TRUE | |

#### `fb_conversations`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `page_id` | VARCHAR(128) | NOT NULL, INDEX | |
| `fb_user_psid` | VARCHAR(128) | NOT NULL, UNIQUE, INDEX | Page-Scoped User ID |
| `first_name` | VARCHAR(128) | NULL | |
| `last_name` | VARCHAR(128) | NULL | |
| `last_interaction_at` | TIMESTAMP | NOT NULL, INDEX | |

#### `fb_messages`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `fb_conversation_id` | BIGINT UNSIGNED | NOT NULL, FK -> `fb_conversations.id` ON DELETE CASCADE, INDEX | |
| `fb_message_mid` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | Meta Message ID (Deduplication) |
| `direction` | ENUM | NOT NULL | `'incoming'`, `'outgoing'` |
| `message_text` | TEXT | NULL | |
| `raw_payload` | JSON | NOT NULL | Complete raw webhook JSON |
| `sent_at` | TIMESTAMP | NOT NULL, INDEX | Meta timestamp |

#### `fb_webhook_events` (Idempotency Table)
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `event_id` | VARCHAR(255) | NOT NULL, UNIQUE, INDEX | Unique webhook payload hash / ID |
| `processed` | BOOLEAN | NOT NULL DEFAULT FALSE, INDEX | |
| `created_at` | TIMESTAMP | NOT NULL | |

#### `incident_intake_records`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `fb_conversation_id` | BIGINT UNSIGNED | NOT NULL, FK -> `fb_conversations.id` ON DELETE CASCADE | |
| `current_step` | VARCHAR(64) | NOT NULL | `'awaiting_category'`, `'awaiting_phone'`, `'awaiting_location'`, `'completed'` |
| `collected_category_id`| BIGINT UNSIGNED | NULL, FK -> `incident_categories.id` | Staged category |
| `collected_phone` | VARCHAR(32) | NULL | Staged phone number |
| `collected_description`| TEXT | NULL | Staged text description |
| `collected_latitude` | DECIMAL(10, 8) | NULL | Staged latitude |
| `collected_longitude`| DECIMAL(11, 8) | NULL | Staged longitude |
| `collected_barangay_id`| BIGINT UNSIGNED | NULL, FK -> `barangays.id` | Staged barangay |
| `is_complete` | BOOLEAN | NOT NULL DEFAULT FALSE, INDEX | Must be TRUE before creating Incident |
| `generated_incident_id`| BIGINT UNSIGNED | NULL, FK -> `incidents.id` ON DELETE SET NULL | Link once formalized |
| `updated_at` | TIMESTAMP | NULL | |

---

### Group J: Notification System

#### `notifications`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | BIGINT UNSIGNED | NOT NULL, FK -> `users.id` ON DELETE CASCADE, INDEX | Target user |
| `title` | VARCHAR(255) | NOT NULL | Notification headline |
| `body` | TEXT | NOT NULL | Notification message |
| `type` | VARCHAR(64) | NOT NULL, INDEX | `incident_dispatched`, `status_updated`, etc. |
| `action_url` | VARCHAR(512) | NULL | Link to navigate upon click |
| `is_read` | BOOLEAN | NOT NULL DEFAULT FALSE, INDEX | |
| `read_at` | TIMESTAMP | NULL | |
| `created_at` | TIMESTAMP | NOT NULL, INDEX | |

#### `notification_deliveries`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `notification_id` | BIGINT UNSIGNED | NOT NULL, FK -> `notifications.id` ON DELETE CASCADE | |
| `channel` | ENUM | NOT NULL | `'websocket'`, `'web_push'`, `'sms'`, `'email'` |
| `status` | ENUM | NOT NULL DEFAULT `'pending'`, INDEX | `'pending'`, `'sent'`, `'delivered'`, `'failed'` |
| `provider_reference`| VARCHAR(255) | NULL | SMS/Push gateway message ID |
| `error_message` | TEXT | NULL | Diagnostics if failed |
| `sent_at` | TIMESTAMP | NULL | |

---

### Group K: System Management

#### `system_settings`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `key` | VARCHAR(128) | NOT NULL, UNIQUE, INDEX | Configuration key |
| `value` | TEXT | NULL | Configuration value |
| `cast_type` | VARCHAR(32) | NOT NULL DEFAULT `'string'` | `string`, `int`, `boolean`, `json` |
| `description` | VARCHAR(255) | NULL | |

#### `emergency_hotlines`
| Field | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| `service_name` | VARCHAR(128) | NOT NULL | e.g. "PNP Hotline", "Red Cross" |
| `phone_number` | VARCHAR(64) | NOT NULL | Dial string |
| `office_id` | BIGINT UNSIGNED | NULL, FK -> `offices.id` | Department affiliation |
| `is_toll_free` | BOOLEAN | NOT NULL DEFAULT FALSE | |
| `sort_order` | INT | NOT NULL DEFAULT 0 | Display priority |

