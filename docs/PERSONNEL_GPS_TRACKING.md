# ALERTO APP — Personnel Real-Time GPS Tracking & Telemetry

## 1. Foundational Privacy & Duty Invariants

1. **Duty-Restricted Tracking**: Location tracking is **ONLY** active when personnel explicitly activate `ONLINE` status and toggle duty location sharing.
2. **Offline Protection**: Going `off_duty` immediately terminates active location sharing. No background coordinates are gathered when off-duty.
3. **No Citizen Tracking**: Citizens are never continuously tracked. Citizen coordinates are recorded exclusively at the moment of incident report submission or address pinning.

---

## 2. Location Freshness Classification

To prevent outdated responder coordinates from being perceived as current during emergency dispatches, ALERTO APP classifies every responder location:

```
┌─────────────────────────────────────────────────────────────┐
│                 TELEMETRY FRESHNESS ENGINE                  │
├───────────────────┬───────────────────┬─────────────────────┤
│ FRESHNESS LEVEL   │ TIME ELAPSED      │ VISUAL REPRESENTATION│
├───────────────────┼───────────────────┼─────────────────────┤
│ Live              │ < 2 minutes       │ Emerald Ring (Pulse)│
│ Recently Updated  │ 2 – 5 minutes     │ Solid Emerald       │
│ Stale             │ >= 5 minutes      │ Amber Warning Pin   │
│ Unavailable       │ No telemetry      │ Grayed Pin          │
└───────────────────┴───────────────────┴─────────────────────┘
```

---

## 3. Telemetry Ingestion Endpoint (`POST /personnel/location`)

Field personnel broadcast updates via the browser HTML5 Geolocation API (`navigator.geolocation.watchPosition`):

```json
POST /personnel/location
Headers:
  Content-Type: application/json
  X-CSRF-TOKEN: ...
Payload:
{
  "latitude": 9.49750000,
  "longitude": 122.58500000,
  "accuracy": 8.5,
  "heading": 180.0,
  "speed": 12.5
}
```

The server stores the coordinate record in `personnel_locations`, refreshes presence and availability heartbeats, and broadcasts `PersonnelLocationUpdated` over authorized private channels.

