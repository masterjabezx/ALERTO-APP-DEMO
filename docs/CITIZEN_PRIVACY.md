# ALERTO APP — Citizen Privacy, Data Isolation & Security

## 1. Overview
The Citizen Portal operates under strict data privacy principles compliant with Republic Act 10173 (Philippine Data Privacy Act of 2012). Citizen personal information, residential coordinates, and emergency call logs are safeguarded against unauthorized access, public enumeration, and data leakage.

---

## 2. Strict Record Ownership & Query Isolation
Citizens are strictly prohibited from viewing, searching, or enumerating incident reports submitted by other citizens.

### Backend Enforcement:
In `CitizenIncidentService.php`:
```php
public function getCitizenIncident(User $reporter, int|string $identifier): ?Incident
{
    $query = Incident::with([...])
        ->where('reporter_id', $reporter->id);

    if (is_numeric($identifier)) {
        return $query->where('id', (int) $identifier)->first();
    }

    return $query->where('reference_number', $identifier)->first();
}
```

In `CitizenPortalController.php`:
```php
public function showRecord(string $reference): View|RedirectResponse
{
    $user = Auth::user();
    $incident = $this->incidentService->getCitizenIncident($user, $reference);

    if (! $incident) {
        abort(404); // Returns 404 to prevent ID enumeration
    }

    return view('citizen.record-detail', [...]);
}
```

> [!IMPORTANT]
> If a user attempts to access an incident reference number belonging to another citizen, the server returns a **404 Not Found** response rather than a 403 Forbidden. This prevents malicious actors from enumerating valid incident reference IDs.

---

## 3. Residential Privacy vs. Operational Dispatch
- The citizen's residential address stored in `citizen_addresses` is strictly isolated.
- Dispatch personnel responding to an active street collision or coastal emergency are only granted access to the incident's scene location (`incident_locations`), never the citizen's home coordinates.
- Contact phone numbers (`caller_phone`) are visible only to authenticated LDRRMO intake dispatchers for verification callbacks.

---

## 4. No Background Continuous Tracking
- ALERTO APP **never** executes background location tracking or silent geofencing on citizens' mobile devices.
- Device GPS coordinates are requested strictly on-demand:
  1. Once during initial registration to identify primary barangay residence.
  2. Once during active incident submission when the citizen explicitly presses "Detect My Location" or loads the emergency intake form.
- The browser Geolocation API prompts the user with standard browser permission dialogs every time.

---

## 5. Audit Logging
Every incident submission, location pin, and status transition is recorded in `audit_logs` with:
- `user_id`: Authenticated citizen ID
- `event_type`: `'incident:submitted'`
- `auditable_type`: `App\Models\Incident`
- `auditable_id`: Generated incident ID
- `ip_address`: Submitter's IP address
- `user_agent`: Submitter's browser user agent
- `created_at`: Exact timestamp

