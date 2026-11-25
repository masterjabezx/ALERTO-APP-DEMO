# ALERTO APP — Office Management Architecture & Administration

## 1. Executive Overview

The **Office Management Module** provides centralized administrative oversight of municipal agency partners and emergency response departments operating within the Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO) ecosystem.

This module guarantees strict multi-agency coordination, operational sovereignty, and resource compartmentalization across municipal agencies including:
- **LDRRMO Operations Center** (Lead coordinating body)
- **BFP — Bureau of Fire Protection** (Fire suppression & HazMat)
- **PNP — Philippine National Police** (Law enforcement & peace and order)
- **RHU — Rural Health Unit / EMS** (Emergency medical response & trauma care)
- **PCG — Philippine Coast Guard** (Maritime rescue & coastal incidents)

---

## 2. Agency Hierarchy & Operational Structure

```
                           ┌───────────────────────────┐
                           │   ADMIN EOC (LDRRMO)      │
                           │ Highest System Authority  │
                           └─────────────┬─────────────┘
                                         │
                 ┌───────────────────────┼───────────────────────┐
                 │                       │                       │
      ┌──────────▼──────────┐ ┌──────────▼──────────┐ ┌──────────▼──────────┐
      │   BFP Station       │ │   PNP Station       │ │   RHU / Health Unit │
      │ - Fire Suppression  │ │ - Law Enforcement   │ │ - Medical & Trauma  │
      │ - Rescue Operations │ │ - Traffic / Crime   │ │ - Ambulance Service │
      └──────────┬──────────┘ └──────────┬──────────┘ └──────────┬──────────┘
                 │                       │                       │
         ┌───────┴───────┐       ┌───────┴───────┐       ┌───────┴───────┐
         │ Response Teams│       │ Response Teams│       │ Response Teams│
         │ Alpha / Bravo │       │ Patrol Units  │       │ Medic Units   │
         └───────────────┘       └───────────────┘       └───────────────┘
```

---

## 3. Administrative Workflows (`/admin/offices`)

### 3.1 Agency CRUD & Directory
System Administrators access `/admin/offices` to manage all external and municipal agencies:
- **Agency Name & Official Code** (e.g., `LDRRMO`, `BFP-HNB`, `PNP-HNB`, `RHU-HNB`).
- **Emergency Hotline Numbers & Official Email Addresses**.
- **Physical Station Headquarters & Staging Bases**.
- **Operational Status Toggle (`is_active`)**: Immediately disables portal logins and incident routing for the target agency when deactivated.

### 3.2 Office User Management (`/admin/offices/{id}/users`)
System Administrators provision and manage accounts tied to specific agencies:
- Create new Office Admin or Office Staff accounts.
- Enforce granular operational flags (`can_review_incidents`, `can_dispatch_units`).
- Toggle individual user account activation.
- Secure administrative password reset.

### 3.3 Agency Responsibilities & Jurisdiction Binding
- **Incident Category Mapping (`office_responsibilities`)**: Binds emergency incident categories (e.g., `structural_fire` -> BFP, `vehicular_accident` -> LDRRMO/PNP/RHU).
- **Territorial Jurisdictions (`office_jurisdictions`)**: Maps official coverage across Hinoba-an's 13 barangays (Alim, Asia, Bacuyangan, Bulwangan, Culipapa, Damutan, Daug, Po-ok, San Rafael, Sangke, Talacagay, Poblacion I, Poblacion II).
- **Response Team Allocation**: Links agency response teams and bases directly to the parent agency.

---

## 4. Database Schema Reference

| Table | Key Columns | Purpose |
|---|---|---|
| `offices` | `id, name, code, contact_number, contact_email, description, is_active, created_at, updated_at` | Primary agency master entity |
| `office_users` | `id, office_id, user_id, office_level, can_review_incidents, can_dispatch_units` | Role level and fine-grained capabilities |
| `office_responsibilities` | `id, office_id, category_slug` | Category routing and review authority |
| `office_jurisdictions` | `id, office_id, barangay_id` | Territorial coverage definitions |

---

## 5. Security & Isolation Invariants
1. **No Super Admin**: System Admin is the apex authority; Office accounts cannot escalate to Admin privileges.
2. **Strict Multi-Tenant Isolation**: Office users can never see, modify, or review incidents outside their agency assignments or authorized categories.
3. **Deactivation Propagation**: Deactivating an agency or office user immediately terminates active sessions via `EnsureActiveAccount` middleware.

