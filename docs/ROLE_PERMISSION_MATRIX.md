# ALERTO APP — Role-Based Authorization & Permission Matrix

## 1. System Role Hierarchy & Guiding Principles

ALERTO APP operates on a **strictly four-tier account structure**:

1. **Citizen** — General public reporting emergencies and viewing community safety bulletins.
2. **Personnel** — Field responders (police, medics, firefighters, search & rescue).
3. **Office** — Departmental operations center (dispatchers, station desk officers, supervisors).
   - *Office Admin:* Department supervisor with intake review and unit dispatch authority.
   - *Office Staff:* Station desk officer with read-only/clerical operational support.
4. **Admin** — Municipal Emergency Operations Center (EOC) director and system administrator.

> [!CAUTION]
> **NO SUPER ADMIN:** The `Admin` account represents the single highest authority in the system. No secondary, hidden, or higher role exists.

---

## 2. Master Permission Matrix

| Capability / Action | Citizen | Personnel | Office Staff | Office Admin | Admin (EOC) |
|---|:---:|:---:|:---:|:---:|:---:|
| **Authentication & Profile** |
| Register self / verify mobile phone | ✅ | ❌ (Created by Admin) | ❌ (Created by Admin) | ❌ (Created by Admin) | ❌ |
| Manage personal profile & address | ✅ (Own) | ✅ (Own) | ✅ (Own) | ✅ (Own) | ✅ (All) |
| Enable Two-Factor Authentication (2FA) | Optional | Required | Required | Required | Required |
| **Incident Management** |
| Submit emergency report (Web/PWA) | ✅ | ✅ | ✅ | ✅ | ✅ (Admin Encoded) |
| View submitted incident status | ✅ (Own only) | ✅ (Assigned only) | ✅ (Office scope) | ✅ (Office scope) | ✅ (System-wide) |
| Review & Accept/Reject incoming reports | ❌ | ❌ | ❌ | ✅ (Office scope) | ✅ (System-wide) |
| Dispatch/Assign units to incident | ❌ | ❌ | ❌ | ✅ (Office units) | ✅ (System-wide) |
| Acknowledge dispatch orders | ❌ | ✅ (Assigned) | ❌ | ❌ | ❌ |
| Update operational response milestones | ❌ | ✅ (Assigned) | ❌ | ❌ | ✅ (Override) |
| Close and finalize resolved incident | ❌ | ❌ | ❌ | ✅ (Office scope) | ✅ (System-wide) |
| **Operations Map & GIS** |
| View public safety advisories map | ✅ | ✅ | ✅ | ✅ | ✅ |
| View live responder GPS locations | ❌ | ✅ (Team only) | ✅ (Office units) | ✅ (Office units) | ✅ (All units) |
| View incident heatmaps & analytical GIS | ❌ | ❌ | ❌ | ✅ (Office scope) | ✅ (System-wide) |
| Edit municipality barangay boundaries | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Internal Messenger** |
| Direct 1-on-1 operational chat | ❌ | ✅ (Authorized) | ✅ (Authorized) | ✅ (Authorized) | ✅ (All) |
| Incident Command chat room | ❌ | ✅ (If assigned) | ✅ (If assigned) | ✅ (Office scope) | ✅ (All) |
| Inter-Office broadcast channel | ❌ | ❌ | ✅ (Read) | ✅ (Post) | ✅ (Full) |
| Citizen-to-Responder direct messaging | ❌ (Via Central) | ❌ | ❌ | ❌ | ❌ |
| **Facebook Messenger Ingestion** |
| View Facebook Messenger intake queue | ❌ | ❌ | ❌ | ✅ (Office scope) | ✅ (System-wide) |
| Trigger Facebook broadcast announcements | ❌ | ❌ | ❌ | ❌ | ✅ |
| **System Administration** |
| Configure routing rules & dispatch matrices| ❌ | ❌ | ❌ | ❌ | ✅ |
| Manage emergency hotlines directory | ❌ | ❌ | ❌ | ❌ | ✅ |
| Manage departments & response teams | ❌ | ❌ | ❌ | ✅ (Own Office) | ✅ (System-wide) |
| View system audit logs & security metrics| ❌ | ❌ | ❌ | ❌ | ✅ |
| Trigger system backups | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## 3. Server-Side Enforcement Architecture

All permissions are strictly enforced through Laravel **Policies** and **Gates**:

- `IncidentPolicy`:
  - `view(User $user, Incident $incident)`: Citizen if `user_id === reporter_id`; Personnel if assigned; Office if `office_id` matches; Admin always.
  - `review(User $user, Incident $incident)`: Admin OR (`Office Admin` matching category/jurisdiction).
  - `dispatch(User $user, Incident $incident)`: Admin OR `Office Admin` after status is `accepted`.
  - `updateMilestone(User $user, Incident $incident)`: Assigned Personnel OR Admin.
- `PresencePolicy`:
  - Enforces that citizen locations are NEVER continuously tracked.
- `MessagePolicy`:
  - Enforces that conversation participants can only read and post within conversations they have been formally joined to.

