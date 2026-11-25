# ALERTO APP Internal Messenger Architecture

## 1. Overview & Operational Scope
The **ALERTO APP Internal Messenger** is the dedicated, encrypted operational dispatch and field coordination system for the **Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO)**. It connects Command Operations, Responding Field Personnel, and Agency Office Representatives in real-time.

### Strict Access Boundaries
- **Authorized Account Types:** `admin`, `personnel`, `office`.
- **Prohibited Account Types:** `citizen` accounts have **NO access** to the internal messenger or its WebSocket channels.
- **Role Hierarchy:** Strictly adheres to the 4 system account types without a Super Admin role.

---

## 2. Communication Topology & Matrix
| Initiator | Allowed Recipient | Context / Channel Type | Permission Rule |
| :--- | :--- | :--- | :--- |
| **Admin** | Personnel, Office, Admin | Direct, Group, Incident | Unrestricted access across all operational channels |
| **Personnel** | Admin | Direct, Incident | Field reporting & dispatch communication with EOC |
| **Office Staff/Admin** | Admin | Direct, Incident | Inter-agency liaison & mutual aid with Command Center |
| **Office Staff/Admin** | Same Office Colleague | Direct | Internal agency collaboration |
| **Personnel** | Personnel | Incident (Group) | Communication permitted within authorized group channels |
| **Citizen** | *None* | *None* | Blocked at route, controller, and channel layers |

---

## 3. Core System Components

### Models & Schema Enhancements
- **`conversations`**:
  - `type`: `direct`, `group`, `incident`
  - `incident_id`: Foreign key linked to `incidents.id` (nullable, unique for incident channels)
  - `title`: Channel name or generated incident title
  - `created_by`: Foreign key to `users.id`
  - `is_archived`: Boolean flag for active/archived state
- **`conversation_participants`**:
  - `conversation_id`, `user_id`, `is_admin`, `last_read_message_id`, `joined_at`
- **`messages`**:
  - `conversation_id`, `sender_id`, `incident_id`, `reply_to_message_id`
  - `body`: Text content (nullable if attachment exists)
  - `message_type`: `text`, `image`, `video`, `document`, `system_event`
  - `edited_at`: Timestamp tracking edits
- **`message_attachments`**:
  - Pivot table linking `messages` and `media_attachments`
- **`media_geo_metadata`**:
  - Coordinates, accuracy, camera info, and strict `gps_source` tracking

### Services & Controllers
- **`App\Services\InternalMessengerService`**: Centralized business logic covering channel creation, permission validation, file storage on `private` disk, geo-tagging ingestion, read receipt logging, and event broadcasting.
- **`App\Http\Controllers\Messenger\InternalMessengerController`**: Serves all three portals (`/admin/messages`, `/office/messages`, `/personnel/messages`) and handles secure attachment streaming.

