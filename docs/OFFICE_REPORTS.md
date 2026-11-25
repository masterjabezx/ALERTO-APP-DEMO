# ALERTO APP — Office Records, Reporting & Data Export

## 1. Executive Overview

The **Records & Reports Module** of the Office Portal (`/office/records`) provides station officers and agency commanders with verified historical archives of emergency incident responses.

This module guarantees:
- Complete compliance with municipal LGU disaster reporting requirements (MDRRMC).
- Transparent response time logging from initial citizen alert to final resolution.
- Scoped data access: Agencies can only audit records directly assigned to them or handled by their response teams.

---

## 2. Multi-Criteria Search & Filter Engine

Station dispatchers can audit historical operations across multiple dimensions:

| Filter Parameter | Options / Format | Operational Impact |
|---|---|---|
| **Date Range** | `date_from` & `date_to` (YYYY-MM-DD) | Monthly and quarterly MDRRMC audits |
| **Incident Status** | `pending_review, accepted, dispatched, on_scene, resolved, rejected, cancelled` | Resolution rate analysis |
| **Incident Category** | `structural_fire, vehicular_accident, medical_emergency, flood_rescue, etc.` | Hazard-specific resource planning |
| **Barangay** | 13 Hinoba-an Barangays | Geographic hotspot identification |
| **Priority Level** | `critical, high, medium, low` | High-severity incident triage review |

---

## 3. Streaming CSV Export Architecture

To eliminate memory overflow when generating large historical datasets, ALERTO APP implements a **memory-efficient streaming CSV generator** (`Symfony\Component\HttpFoundation\StreamedResponse`).

### Technical Specification (`OfficePortalController::exportRecords`)
- **Route**: `GET /office/records/export`
- **Output Mime-Type**: `text/csv; charset=UTF-8`
- **Header Directives**:
  - `Content-Disposition: attachment; filename="{agency_code}-incidents-{date}.csv"`
  - `Cache-Control: no-cache, no-store, must-revalidate`
- **Chunked Processing**: Records are retrieved in cursor-like memory streams (`fputcsv`), preventing PHP memory exhaustion during municipal audits.

### Exported Column Structure

```csv
Reference Number,Category,Barangay,Caller Name,Caller Phone,Status,Priority,Submitted At
INC-2026-1042-TEST,Structural Fire,Poblacion I,Juan Dela Cruz,09171234567,RESOLVED,CRITICAL,2026-11-25 04:12:00
INC-2026-1043-TEST,Vehicular Accident,Culipapa,Maria Santos,09187654321,ON_SCENE,HIGH,2026-11-25 05:30:15
```

---

## 4. Privacy & Data Protection Compliance

1. **Confidential Reporting Protection**:
   If an incident was reported anonymously or marked confidential, citizen contact fields are automatically masked as `Confidential` or `Protected` in exported reports.
2. **Access Authorization**:
   Export capabilities are strictly protected by `can_review_incidents` or agency administrative standing.
3. **Audit Logging**:
   Every report export action is recorded in `audit_logs` with the requesting officer's ID, timestamp, and IP address.

