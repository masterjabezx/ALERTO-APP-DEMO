# ALERTO APP — Real-Time Map Broadcasting & WebSocket Architecture

## 1. Broadcasting Overview

ALERTO APP utilizes **Laravel Broadcasting** and **Laravel Reverb** to deliver instant, asynchronous spatial updates to the Emergency Operations Center without manual browser refresh.

---

## 2. Broadcast Channels & Security Rules

All mapping communication occurs strictly over authenticated private channels (`routes/channels.php`):

```
Channel Name: `private-admin.operations`
Authorization: `$user->isAdmin()`
Purpose: Full municipal telemetry, live pins, responder updates, review status changes.

Channel Name: `private-office.{officeId}`
Authorization: `$user->isOffice() && $user->officeUsers()->where('office_id', $officeId)->exists()`
Purpose: Scoped dispatches, assigned incidents, and agency unit telemetry.

Channel Name: `private-personnel.{userId}`
Authorization: `(int)$user->id === (int)$userId && $user->isPersonnel()`
Purpose: Targeted dispatch alerts, route navigation updates, personal task alerts.
```

---

## 3. Registered Event Matrix

| Event Class | Trigger Action | Targeted Channels |
|---|---|---|
| `IncidentCreated` | Citizen report submitted | `admin.operations` |
| `IncidentAccepted` | Report approved by Admin/Office | `admin.operations`, `office.{id}` |
| `IncidentAssigned` | Agency/Responder assigned | `admin.operations`, `office.{id}`, `personnel.{id}` |
| `IncidentDispatched` | Official dispatch authorized | `admin.operations`, `office.{id}`, `personnel.{id}` |
| `IncidentAcknowledged` | Responder accepts dispatch | `admin.operations`, `office.{id}` |
| `IncidentStatusUpdated` | Milestone status advanced | `admin.operations`, `office.{id}`, `personnel.{id}` |
| `PersonnelLocationUpdated` | Live GPS coordinate reported | `admin.operations`, `office.{id}` |
| `PersonnelAvailabilityChanged` | Online/Offline toggle | `admin.operations`, `office.{id}` |
| `OfficeIncidentUpdated` | Agency operational changes | `admin.operations`, `office.{id}` |
| `IncidentResolved` | Emergency scene mitigated | `admin.operations`, `office.{id}`, `personnel.{id}` |
| `IncidentClosed` | Incident archived | `admin.operations` |

---

## 4. Controlled Polling Fallback

To guarantee operations during intermittent WebSocket disconnects or cellular dead zones, the client interfaces automatically execute controlled background polling to `/admin/map/data`, `/office/map/data`, and `/personnel/map/data` every 15–20 seconds, merging incoming telemetry seamlessly.

