# ALERTO APP — Entity Relationship Diagrams (ERD)

This document visualizes the complete database architecture of ALERTO APP, partitioned into logical domain sub-diagrams for clarity and maintainability.

---

## 1. Core Authentication, Access Control & Auditing

```mermaid
erDiagram
    users ||--o{ role_user : "assigned"
    roles ||--o{ role_user : "groups"
    roles ||--o{ permission_role : "grants"
    permissions ||--o{ permission_role : "defines"
    users ||--o{ permission_user : "direct override"
    permissions ||--o{ permission_user : "overrides"
    users ||--|| user_presences : "tracks presence"
    users ||--o{ audit_logs : "triggers"

    users {
        bigint id PK
        string name
        string email
        string phone_number UK
        enum account_type "citizen, personnel, office, admin"
        boolean is_active
        timestamp created_at
    }

    roles {
        bigint id PK
        string name UK "admin, office, personnel, citizen"
        string display_name
    }

    permissions {
        bigint id PK
        string name UK
        string group_name
    }

    user_presences {
        bigint id PK
        bigint user_id FK, UK
        enum status "online, idle, offline"
        timestamp last_heartbeat_at
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        string event_type
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
        timestamp created_at
    }
```

---

## 2. Citizen, Personnel, & Office Profiles

```mermaid
erDiagram
    users ||--o| citizen_profiles : "has"
    users ||--o{ citizen_addresses : "registered home"
    barangays ||--o{ citizen_addresses : "located within"
    users ||--o| personnel_profiles : "has"
    offices ||--o{ personnel_profiles : "employs"
    personnel_profiles ||--|| personnel_availabilities : "current readiness"
    offices ||--o{ response_teams : "operates"
    response_teams ||--o{ team_memberships : "consists of"
    personnel_profiles ||--o{ team_memberships : "assigned to"
    personnel_profiles ||--o{ personnel_locations : "GPS telemetry"
    offices ||--o{ office_users : "staffed by"
    users ||--o{ office_users : "works at"
    offices ||--|| office_locations : "station coordinates"
    barangays ||--o{ office_locations : "stationed in"

    citizen_profiles {
        bigint id PK
        bigint user_id FK, UK
        string first_name
        string last_name
        string blood_type
        boolean verified_citizen
    }

    citizen_addresses {
        bigint id PK
        bigint user_id FK
        bigint barangay_id FK
        string street_address
        decimal latitude
        decimal longitude
    }

    personnel_profiles {
        bigint id PK
        bigint user_id FK, UK
        bigint office_id FK
        string badge_number UK
        string rank_designation
        string specialization
    }

    personnel_availabilities {
        bigint id PK
        bigint personnel_id FK, UK
        enum operational_status "available, dispatched, on_scene, busy, off_duty"
        timestamp status_updated_at
    }

    personnel_locations {
        bigint id PK
        bigint personnel_id FK
        decimal latitude
        decimal longitude
        decimal heading
        decimal speed
        timestamp recorded_at
    }

    offices {
        bigint id PK
        string name
        string code UK
        enum department_type "police, fire, medical, disaster, traffic"
        string contact_phone
    }

    office_users {
        bigint id PK
        bigint office_id FK
        bigint user_id FK
        enum office_level "office_admin, office_staff"
        boolean can_review_incidents
        boolean can_dispatch_units
    }
```

---

## 3. Geographic Infrastructure

```mermaid
erDiagram
    municipalities ||--o{ barangays : "contains"
    barangays ||--|| barangay_boundaries : "boundary coordinates"

    municipalities {
        bigint id PK
        string name
        string province
        string region
        decimal center_latitude
        decimal center_longitude
    }

    barangays {
        bigint id PK
        bigint municipality_id FK
        string name
        decimal center_latitude
        decimal center_longitude
    }

    barangay_boundaries {
        bigint id PK
        bigint barangay_id FK, UK
        longtext geojson_boundary
        decimal bounding_min_lat
        decimal bounding_max_lat
        decimal bounding_min_lng
        decimal bounding_max_lng
    }
```

---

## 4. Incident Management, Lifecycle & Dispatch Routing

```mermaid
erDiagram
    users ||--o{ incidents : "reported by"
    incident_categories ||--o{ incidents : "classified under"
    barangays ||--o{ incidents : "occurred at"
    incidents ||--|| incident_locations : "pinned at"
    incidents ||--o{ incident_reviews : "evaluated by"
    users ||--o{ incident_reviews : "reviewer"
    incidents ||--o{ incident_assignments : "dispatched to"
    offices ||--o{ incident_assignments : "department"
    response_teams ||--o{ incident_assignments : "dispatched team"
    personnel_profiles ||--o{ incident_assignments : "dispatched operative"
    incidents ||--o{ incident_status_histories : "lifecycle log"
    incidents ||--o{ response_logs : "milestones"
    incident_categories ||--o{ incident_routing_rules : "dictates"
    offices ||--o{ incident_routing_rules : "target office"

    incidents {
        bigint id PK
        string reference_number UK
        bigint reporter_id FK
        enum source_channel "citizen_portal, facebook_messenger, admin_encoded"
        bigint category_id FK
        enum priority "critical, high, medium, low"
        enum status "submitted, pending_review, accepted, dispatched, acknowledged, on_the_way, on_scene, in_progress, resolved, closed, rejected"
        string caller_name
        string caller_phone
        bigint barangay_id FK
        timestamp submitted_at
        int version_lock
    }

    incident_locations {
        bigint id PK
        bigint incident_id FK, UK
        decimal latitude
        decimal longitude
        string street_address
        string landmark
        enum location_source
    }

    incident_reviews {
        bigint id PK
        bigint incident_id FK
        bigint reviewed_by FK
        enum decision "accepted, rejected"
        string rejection_reason
        timestamp reviewed_at
    }

    incident_assignments {
        bigint id PK
        bigint incident_id FK
        bigint office_id FK
        bigint team_id FK
        bigint personnel_id FK
        bigint assigned_by FK
        enum status "active, reassigned, completed, cancelled"
        timestamp assigned_at
        timestamp acknowledged_at
    }

    incident_status_histories {
        bigint id PK
        bigint incident_id FK
        string previous_status
        string new_status
        bigint changed_by FK
        timestamp created_at
    }

    response_logs {
        bigint id PK
        bigint incident_id FK
        bigint assignment_id FK
        bigint personnel_id FK
        enum milestone "acknowledged, on_the_way, on_scene, in_progress, cleared"
        decimal latitude
        decimal longitude
        timestamp recorded_at
    }
```

---

## 5. Media & Geo-Tagged Evidence

```mermaid
erDiagram
    users ||--o{ media_attachments : "uploaded by"
    media_attachments ||--|| media_geo_metadata : "spatial telemetry"
    incidents ||--o{ incident_evidence_records : "evidence items"
    media_attachments ||--o{ incident_evidence_records : "evidence file"

    media_attachments {
        bigint id PK
        bigint uploader_id FK
        string file_path
        string mime_type
        bigint file_size_bytes
        char checksum_sha256
        timestamp created_at
    }

    media_geo_metadata {
        bigint id PK
        bigint media_id FK, UK
        decimal latitude
        decimal longitude
        decimal accuracy_meters
        enum gps_source "exif_embedded, device_live_upload, incident_inherited, none"
        boolean is_inherited
        timestamp captured_at
    }

    incident_evidence_records {
        bigint id PK
        bigint incident_id FK
        bigint media_id FK
        enum evidence_type "citizen_submission, responder_on_scene, damage_assessment, resolution_proof"
        string caption
    }
```

---

## 6. Internal Messenger System

```mermaid
erDiagram
    conversations ||--o{ conversation_participants : "members"
    users ||--o{ conversation_participants : "participates"
    incidents ||--o| conversations : "incident chat thread"
    conversations ||--o{ messages : "contains"
    users ||--o{ messages : "sent by"
    messages ||--o{ message_attachments : "attaches"
    media_attachments ||--o{ message_attachments : "file"
    messages ||--o{ message_read_receipts : "read state"
    users ||--o{ message_read_receipts : "acknowledged by"

    conversations {
        bigint id PK
        enum type "direct, group, incident"
        bigint incident_id FK, UK
        string title
        bigint created_by FK
        timestamp updated_at
    }

    conversation_participants {
        bigint id PK
        bigint conversation_id FK
        bigint user_id FK
        boolean is_admin
        bigint last_read_message_id
    }

    messages {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK
        text body
        enum message_type "text, media, system_event, location"
        timestamp created_at
    }

    message_read_receipts {
        bigint id PK
        bigint message_id FK
        bigint user_id FK
        timestamp read_at
    }
```

---

## 7. Facebook Messenger Intake System

```mermaid
erDiagram
    fb_conversations ||--o{ fb_messages : "exchanges"
    fb_conversations ||--o{ incident_intake_records : "stages report"
    incident_categories ||--o{ incident_intake_records : "intake category"
    barangays ||--o{ incident_intake_records : "intake barangay"
    incidents ||--o| incident_intake_records : "formalized incident"

    fb_conversations {
        bigint id PK
        string page_id
        string fb_user_psid UK
        string first_name
        string last_name
        timestamp last_interaction_at
    }

    fb_messages {
        bigint id PK
        bigint fb_conversation_id FK
        string fb_message_mid UK
        enum direction "incoming, outgoing"
        text message_text
        json raw_payload
        timestamp sent_at
    }

    incident_intake_records {
        bigint id PK
        bigint fb_conversation_id FK
        string current_step "awaiting_category, awaiting_phone, awaiting_location, completed"
        bigint collected_category_id FK
        string collected_phone
        text collected_description
        decimal collected_latitude
        decimal collected_longitude
        bigint collected_barangay_id FK
        boolean is_complete
        bigint generated_incident_id FK
    }
```

---

## 8. Multi-Channel Notification Pipeline

```mermaid
erDiagram
    users ||--o{ notifications : "receives"
    notifications ||--o{ notification_deliveries : "dispatches via"

    notifications {
        bigint id PK
        bigint user_id FK
        string title
        text body
        string type
        string action_url
        boolean is_read
        timestamp created_at
    }

    notification_deliveries {
        bigint id PK
        bigint notification_id FK
        enum channel "websocket, web_push, sms, email"
        enum status "pending, sent, delivered, failed"
        string provider_reference
        text error_message
        timestamp sent_at
    }
```

