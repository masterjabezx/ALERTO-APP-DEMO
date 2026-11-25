# ALERTO APP — Incident Review & Acceptance Workflow

## 1. Overview
All emergency submissions originating from the Citizen Portal or external channels enter the system in a locked state:
```sql
status = 'pending_review'
```
No incident is ever automatically accepted or dispatched. A human operator or authorized officer must review, triage, and decide the outcome of the report.

---

## 2. Review Authorities & RBAC Matrix

| Role / Account Type | Permissions on Triage Review | Scope |
|---|---|---|
| **Admin** | Full system authority (`review`, `override`, `updatePriority`) | All municipal incidents across all categories and barangays |
| **Office Admin** | Authorized review (`review`) | Incidents matching their office jurisdiction, responsibility, or assigned queue |
| **Office Staff** | Read-only unless explicitly granted `can_review_incidents = true` | Permitted assigned department incidents only |
| **Personnel** | None | Field operations only; cannot accept/reject incidents |
| **Citizen** | None | Submitter only |

---

## 3. The Two Primary Actions

### A. Accept Incident (`decision = 'accepted'`)
- **Reviewer:** Authenticated user ID recorded in `reviewed_by`.
- **Timestamp:** Exact timestamp stored in `reviewed_at` and `accepted_at`.
- **Review Remarks:** Recorded in `review_notes`.
- **Status Transition:** `pending_review` ➔ `accepted`.
- **Routing Engine:** Evaluates category rules, barangay boundaries, and live responder availability to generate response team recommendations.
- **Citizen Notification:** In-app notification sent to the reporting citizen:
  > *"Your report INC-YYYY-XXXX has been verified and accepted by LDRRMO. Emergency response coordination is underway."*
- **Audit Trail:** Logged in `audit_logs` under `incident:accepted`.

### B. Reject Incident (`decision = 'rejected'`)
- **Mandatory Rejection Reason:** Strict validation requires selecting or inputting a reason (e.g. *Prank / Malicious Report*, *Duplicate Report*, *Unreachable / Fake Contact Number*, *Outside Municipal Jurisdiction*).
- **Reviewer & Timestamp:** Recorded in `incident_reviews`.
- **Status Transition:** `pending_review` ➔ `rejected`.
- **Preservation:** The record is **never** deleted. It remains visible in the `Rejected Incidents` archive.
- **Citizen Notification:** In-app notification sent to the reporter explaining the rejection reason.
- **Audit Trail:** Logged in `audit_logs` under `incident:rejected`.

---

## 4. Concurrency Protection & Row Locking
To prevent race conditions where two operators simultaneously review the same report, reviews execute within database transactions utilizing pessimistic locking:
```php
$lockedIncident = Incident::where('id', $incident->id)->lockForUpdate()->firstOrFail();

if ($lockedIncident->status !== 'pending_review' && ! $reviewer->isAdmin()) {
    throw new InvalidArgumentException("Incident is not currently pending review.");
}

$lockedIncident->version_lock++;
```

---

## 5. Administrative Decision Override
System Administrators retain the operational authority to reverse or correct an earlier triage decision (e.g. re-opening an erroneously rejected call):
- Requires an explicit, audited override justification.
- Records the override event in `incident_status_histories` and `audit_logs`.

