# ALERTO APP — Office Incident Monitoring & Scoped Coordination

## 1. Operational Overview

Incident monitoring within the **Office Portal** is designed to provide station dispatchers and watch officers with complete, real-time situational awareness without exposing unrelated municipal incidents or sensitive data belonging to external agencies.

Every incident displayed within the Office Portal is strictly scoped to:
1. **Directly Assigned Incidents**: Incidents where the agency has been explicitly assigned via `incident_assignments`.
2. **Team-Assigned Incidents**: Incidents assigned to response teams belonging to this agency.
3. **Agency Responsibilities**: Incidents matching categories registered in `office_responsibilities` or routed via `incident_routing_rules`.

---

## 2. Multi-Tenant Scoping Architecture

```
                    ┌────────────────────────────┐
                    │      INCOMING INCIDENT     │
                    │   Category: Structural Fire│
                    │   Barangay: Poblacion I    │
                    └─────────────┬──────────────┘
                                  │
                                  ▼
                    ┌────────────────────────────┐
                    │    Routing & Assignments   │
                    └──────┬──────────────┬──────┘
                           │              │
           ┌───────────────▼┐            ┌▼───────────────┐
           │ Assigned: BFP  │            │ Assigned: RHU  │
           │ (Fire Engine)  │            │ (EMS Ambulance)│
           └───────┬────────┘            └────────┬───────┘
                   │                              │
                   ▼                              ▼
          Visible to BFP Desk            Visible to RHU Desk
          (Hidden from PNP, PCG)         (Hidden from PNP, PCG)
```

### Query Scoping Implementation (`OfficeManagementService::getScopedIncidentsQuery`)
```php
$teamIds = ResponseTeam::where('office_id', $office->id)->pluck('id');
$assignedIncidentIds = IncidentAssignment::where('office_id', $office->id)
    ->orWhereIn('team_id', $teamIds)
    ->pluck('incident_id');

$responsibleCategories = OfficeResponsibility::where('office_id', $office->id)
    ->pluck('category_slug');

return Incident::query()
    ->where(function ($q) use ($assignedIncidentIds, $responsibleCategories) {
        $q->whereIn('id', $assignedIncidentIds)
          ->orWhereHas('category', fn ($c) => $c->whereIn('slug', $responsibleCategories));
    });
```

---

## 3. Incident Review & Triage Workflow

### 3.1 Acceptance Workflow
When an incident is routed to an agency with status `pending_review`:
1. The **Office Admin** inspects caller verification details, geolocation coordinates, and reported description.
2. Submitting an `ACCEPT` review records an entry in `incident_reviews` (`decision = 'accepted'`) and advances the incident status to `accepted`.
3. The incident enters the queue for response team assignment and operational dispatch.

### 3.2 Rejection Workflow
If the incident is duplicate, malicious, or outside operational jurisdiction:
1. The **Office Admin** selects `REJECT`.
2. A **mandatory rejection category** must be specified (`duplicate`, `invalid_information`, `out_of_jurisdiction`, `hoax_or_malicious`, `unresponsive_caller`, `other`).
3. A **mandatory written justification** must be entered (`rejection_reason`).
4. Submitting marks the incident as `rejected` in `incidents`, logs the review record, and notifies the Admin EOC for oversight.

> [!IMPORTANT]
> **Acceptance is strictly distinct from Dispatch**:
> Acceptance acknowledges intake and confirms agency responsibility. It does **NOT** bypass central dispatch authorization or active team assignment prerequisites.

---

## 4. Visual Timeline & Audit Trail
Every incident displays the chronological `incident_status_histories` trail:
- Submission by Citizen (Timestamp & Geolocation).
- Contact Verification by Dispatcher (Verified / Callback pending).
- Triage Review by Office Admin (Accepted / Rejected with remarks).
- Operational Dispatch (Units alerted & mobilized).
- Tactical Milestones (`on_the_way`, `on_scene`, `resolved`).

