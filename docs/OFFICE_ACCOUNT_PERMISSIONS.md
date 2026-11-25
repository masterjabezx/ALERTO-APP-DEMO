# ALERTO APP — Office Account Structure & Granular Permission Matrix

## 1. Foundational Architecture Rule

ALERTO APP strictly defines **EXACTLY FOUR MAIN ACCOUNT TYPES**:
1. `citizen`
2. `personnel`
3. `office`
4. `admin`

**THERE IS NO SUPER ADMIN.**

The `admin` account type represents the highest system authority (LDRRMO Executive Command).

Within the `office` account type, permission levels and operational authorizations are strictly governed by the `office_users` pivot table, **NOT** by creating additional top-level account types.

---

## 2. Office Permission Levels

```
               ┌────────────────────────────────────────────────────────┐
               │                  OFFICE ACCOUNT TYPE                   │
               └───────────────────────────┬────────────────────────────┘
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    │                                             │
         ┌──────────▼──────────┐                       ┌──────────▼──────────┐
         │    office_admin     │                       │    office_staff     │
         │ - Station Commander │                       │ - Desk Watchman     │
         │ - Triage Review     │                       │ - Communications    │
         │ - Unit Dispatch     │                       │ - Read-Only Monitor │
         └─────────────────────┘                       └─────────────────────┘
```

### 2.1 Office Admin (`office_level = 'office_admin'`)
- **Station Commander / Officer-in-Charge**: Holds agency-level authority.
- **Can Review Incidents (`can_review_incidents = true`)**: Authorized to accept or reject incoming emergency incident reports routed to the agency.
- **Can Dispatch Units (`can_dispatch_units = true`)**: Authorized to recommend or assign agency response teams and personnel.
- **Can Access Reports & Exports**: Authorized to download official operational datasets.

### 2.2 Office Staff (`office_level = 'office_staff'`)
- **Watch Desk Officer / Radio Operator**: Responsible for situational monitoring and communications.
- **Read-Only Incident Access**: Monitors active incidents and operational feeds.
- **Restricted from Review Decisions (`can_review_incidents = false`)**: Cannot accept or reject incidents. Any attempt triggers HTTP 403 Forbidden.
- **Restricted from Dispatch Overrides (`can_dispatch_units = false`)**: Cannot execute administrative dispatch actions.

---

## 3. Comprehensive Role & Permission Matrix

| Operation / Feature | Citizen | Personnel | Office Staff | Office Admin | System Admin |
|---|:---:|:---:|:---:|:---:|:---:|
| Access Office Portal (`/office/*`) | ❌ | ❌ | ✅ | ✅ | ✅ (Supervisory) |
| View Office Scoped Incidents | ❌ | ❌ | ✅ | ✅ | ✅ |
| View Full Caller Verification Info | ❌ | Scoped | ✅ | ✅ | ✅ |
| Accept Emergency Incident | ❌ | ❌ | ❌ (403) | ✅ (Scoped) | ✅ |
| Reject Incident (With Reason) | ❌ | ❌ | ❌ (403) | ✅ (Scoped) | ✅ |
| Administrative Review Override | ❌ | ❌ | ❌ | ❌ | ✅ |
| Execute Unit Dispatch Alert | ❌ | ❌ | ❌ | ✅ (Pre-reqs met) | ✅ |
| Export Historical Reports (CSV) | ❌ | ❌ | ✅ (Scoped) | ✅ (Scoped) | ✅ (Full) |
| Send EOC Messages | ❌ | ✅ (Field) | ✅ | ✅ | ✅ |
| Manage Agency Personnel Accounts | ❌ | ❌ | ❌ | ❌ | ✅ |
| Configure Agency Jurisdictions | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## 4. Enforcement Mechanisms

1. **Route Middleware (`EnsureAccountType:office`)**:
   Blocks citizens, field personnel, and unauthenticated guests from entering `/office/*` routes.
2. **Controller Scope Guard (`OfficePortalController::getAuthorizedOffice`)**:
   Resolves the authenticated user's assigned agency from `office_users`. Users not tied to an active agency are rejected with HTTP 403.
3. **Form Request Authorization (`OfficeReviewIncidentRequest::authorize`)**:
   Strictly requires `officeUser->can_review_incidents === true`.
4. **Model Capability Scope (`User::canReviewIncident`)**:
   Verifies that the target incident's category or assignment matches the user's agency responsibilities (`office_responsibilities`, `IncidentRoutingRule`, or `IncidentAssignment`).
