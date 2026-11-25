# ALERTO APP — Seeder Documentation & Sample Data Registry

## 1. Executed Seeders

### 1. `RoleAndPermissionSeeder`
- Seeded the **strictly four (4)** account roles: `admin`, `office`, `personnel`, `citizen`. (Confirmed: **NO Super Admin**).
- Seeded 19 granular permissions across incidents, GIS maps, internal messenger, and system configuration.
- Linked permissions to role groups.

### 2. `GeographicSeeder`
- Primary Municipality: **General Santos City** (South Cotabato, Region XII).
- 6 Key Barangays with Centroids and GeoJSON boundary boxes:
  1. Dadiangas Central
  2. Dadiangas North
  3. Dadiangas South
  4. Calumpang
  5. Lagao
  6. Labangal

### 3. `IncidentCategorySeeder`
- 5 Standard Emergency Categories with SLA priorities, icons, and theme colors:
  1. Fire Emergency (`#EF4444`, Critical)
  2. Medical Emergency (`#F43F5E`, Critical)
  3. Crime & Security (`#3B82F6`, High)
  4. Disaster & Rescue (`#F59E0B`, Critical)
  5. Traffic Hazard & Collision (`#EAB308`, Medium)

### 4. `OfficeAndTeamSeeder`
- 4 Primary Emergency Stations with hotlines and initial response units:
  1. General Santos City Police Station (PNP) — Teams: `PATROL-01`, `TACTICAL-01`
  2. Bureau of Fire Protection - Central (BFP) — Teams: `ENGINE-01`, `LADDER-01`
  3. City Disaster Risk Reduction & Management Office (CDRRMO) — Teams: `RESCUE-01`, `WASAR-01`
  4. City Health Office - Emergency Medical Services (EMS) — Teams: `MEDIC-01`, `MEDIC-02`

### 5. `DevelopmentAccountSeeder`
All development accounts are strictly segregated and labeled as development test entities.

| Role / Account Type | Name | Phone Number | Email | Default Dev Password |
|---|---|---|---|---|
| **Admin** (Highest Authority) | ALERTO EOC Director (Dev) | `09170000001` | `admin@alerto.local` | `AlertoDev2026!` |
| **Office Admin** | Commander Juan Desk (Dev) | `09170000002` | `office.admin@alerto.local` | `AlertoDev2026!` |
| **Office Staff** | Officer Maria Clerk (Dev) | `09170000003` | `office.staff@alerto.local` | `AlertoDev2026!` |
| **Personnel** | Responder Alex Cruz (Dev) | `09170000004` | `personnel.medic@alerto.local` | `AlertoDev2026!` |
| **Citizen** | Citizen Pedro Santos (Dev) | `09170000005` | `citizen.demo@alerto.local` | `AlertoDev2026!` |

