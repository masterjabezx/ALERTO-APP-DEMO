# ALERTO APP — Citizen Registration & Residential Location Pinning

## 1. Overview
Citizen registration in ALERTO APP establishes a verified profile for residents of Hinoba-an, Negros Occidental while anchoring their primary residential location. This geographic anchor enables the LDRRMO dispatch system to identify resident proximity during localized disasters such as river overflow, typhoon surges, or landslide risk warnings.

---

## 2. Registration Data Flow & Validation

```
+-------------------------------------------------------------------------+
|                          CITIZEN REGISTRATION                           |
|                                                                         |
|  [Step 1: Identity]       First Name, Last Name, Gender, DOB            |
|  [Step 2: Contact]        Phone Number (09XXXXXXXXX), Email (optional)  |
|  [Step 3: Security]       Password (min 8 characters)                   |
|  [Step 4: Resident Base]  Barangay Selection (13 Hinoba-an Barangays)   |
|  [Step 5: Mandatory GPS]  Device Permission -> Latitude & Longitude     |
|                           Manual Pin Adjustment Fallback                |
+-------------------------------------------------------------------------+
                                    │
                                    ▼
                         Database Transaction
            ┌───────────────────────┴───────────────────────┐
            ▼                                               ▼
      `users` table                              `citizen_addresses` table
  (Identity & Credentials)                       (Residential Coordinates)
```

### Validation Rules:
- **Phone Number:** Required, formatted as `09XXXXXXXXX` (11 digits, Philippine mobile network prefixes).
- **Barangay:** Required, must exist within the 13 official barangays of Hinoba-an (`barangays` table).
- **Residential Latitude / Longitude:** Mandatory coordinates. Captured automatically via browser geolocation API or placed manually on the interactive map picker.
- **Password:** Minimum 8 characters, confirmed.

---

## 3. Strict Separation of Residential vs. Incident Coordinates
A fundamental architectural and privacy principle of ALERTO APP:

| Coordinate Type | Storage Table | Purpose | Privacy Classification |
|---|---|---|---|
| **Residential Location** | `citizen_addresses` (`is_primary = true`) | Resident demographic record, vulnerability mapping, post-disaster welfare checks | **Confidential** — Never displayed on public incident feeds; accessible only to authorized LDRRMO officers |
| **Incident Location** | `incident_locations` | Specific scene where emergency assistance is urgently required | **Operational** — Broadcast to assigned dispatch responders and command center |

> [!IMPORTANT]
> The citizen's residential address coordinates are **NEVER** automatically overwritten by incident coordinates. When a citizen reports an emergency occurring outside their residence (e.g., highway road collision or riverside flood), the incident location is stored solely in `incident_locations` linked to that specific `incident_id`.

---

## 4. Device Geolocation Permission & Fallback Flow
1. **Permission Request:** Upon reaching the location step, the registration form invokes `navigator.geolocation.getCurrentPosition()`.
2. **Permission Granted:** Coordinates (`latitude`, `longitude`, `accuracy`) populate the form fields and center the interactive map pin.
3. **Permission Denied / Timeout:**
   - A friendly notification informs the user that GPS permission was not granted.
   - The map centers on the selected Barangay's municipal centroid (`barangays.center_latitude`, `barangays.center_longitude`).
   - The citizen manually drags or taps the map marker to pinpoint their exact home location.
   - Coordinates are saved with validation ensuring they lie within the Hinoba-an municipal boundary.

