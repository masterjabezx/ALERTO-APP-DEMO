# ALERTO APP — Official Incident Dispatch Guide

## 1. The Cardinal Dispatch Rule
> [!CRITICAL]
> **ACCEPTANCE IS NOT THE SAME AS DISPATCH.**
> 
> Under no circumstances does accepting an incident automatically dispatch field responders. Official dispatch is an independent, confirmed operational authorization step.

---

## 2. Complete Step-by-Step Incident Lifecycle

```
[1. Citizen Submits] ──> Status: PENDING REVIEW
                               │
[2. Triage Call Callback] ─────┼──> Status: PENDING REVIEW
                               │
[3. Operator Accepts] ─────────┴──> Status: ACCEPTED
                                          │
[4. Routing Engine Evaluates] ────────────┼──> Status: ACCEPTED
                                          │
[5. Admin Confirms Assignment] ───────────┴──> Status: ACCEPTED (Assignment Active)
                                                     │
[6. Admin Authorizes Official Dispatch] ─────────────┴──> Status: DISPATCHED
                                                                │
[7. Personnel Acknowledges Task] ───────────────────────────────┼──> Status: ACKNOWLEDGED
                                                                │
[8. Responders Mobilize En Route] ──────────────────────────────┼──> Status: ON THE WAY
                                                                │
[9. Responders Arrive on Scene] ────────────────────────────────┼──> Status: ON SCENE
                                                                │
[10. Incident Mitigation / Action] ─────────────────────────────┼──> Status: IN PROGRESS
                                                                │
[11. Scene Cleared / Resolved] ─────────────────────────────────┴──> Status: RESOLVED / CLOSED
```

---

## 3. Dispatch Prerequisites
Before official dispatch can be executed, the system enforces two hard server-side validations:
1. **Incident Must Be `accepted`:** Calling dispatch on an incident in `pending_review` or `rejected` is blocked.
2. **Confirmed Assignment Must Exist:** The incident must possess at least one active assignment (`incident_assignments.status = 'active'`). Calling dispatch with no assigned unit is blocked.

---

## 4. Targeted Alert Transmission
To prevent panic, operational confusion, and radio congestion, dispatch alerts are transmitted **strictly to confirmed assigned recipients**:
- **Assigned Personnel:** Each designated responder receives an urgent in-app notification:
  > *"EMERGENCY DISPATCH ORDER: You have been dispatched to [Category] ([Reference]) at [Barangay]."*
- **Assigned Office Dispatchers:** Office dispatch desks receive departmental dispatch advisories.
- **Unassigned Personnel:** Receive **zero** dispatch alerts.
- **Reporting Citizen:** Receives an advisory notification confirming that units have been mobilized to their scene.

