# ALERTO Incident-Linked Messaging & Dispatch Coordination

## 1. Operational Concept
Emergency situations require focused, contextual coordination. Rather than discussing ongoing emergencies in generic chat rooms, ALERTO APP automatically creates and links dedicated incident channels:
- Channel Title: `Incident #INC-YYYY-XXXX - [Category Name]`
- Foreign Key: `conversations.incident_id`
- Unique Constraint: Exactly one primary incident coordination channel per incident.

---

## 2. "Message Admin About This Incident" Flow
Available across:
- **Admin Portal:** Incident Overview & Management (`/admin/incidents/{reference}`)
- **Office Portal:** Agency Incident Show Page (`/office/incidents/{reference}`)
- **Personnel Portal:** Active Emergency Incidents List (`/personnel/active-incidents`)

### Automated Routing:
1. User clicks **"Message Admin About This Incident"**.
2. Endpoint triggered:
   - Admin: `GET /admin/messages/incident/{reference}`
   - Office: `GET /office/messages/incident/{reference}`
   - Personnel: `GET /personnel/messages/incident/{reference}`
3. System verifies incident existence and user role.
4. If channel already exists:
   - Creator is verified/added as a participant.
   - Redirects into the channel with full prior history.
5. If channel does not exist:
   - Channel is initialized with `type = 'incident'`.
   - Creator and all active Admin accounts are attached as participants.
   - Broadcast events notify EOC Command immediately.

---

## 3. Incident Context Sidebar (Panel 3)
When viewing an active incident channel in the Admin messenger, Panel 3 renders:
- Reference number, Category, Severity level, Operational status.
- Geographical address & landmark.
- One-click shortcuts to **Full Incident Details** and **Live Operations Map**.
- Roster of Mobilized Units & Responding Personnel.
- Shared Evidence Media Gallery (photos, videos, SitReps).

