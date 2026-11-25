# ALERTO APP — Response Team Management Architecture

## Overview
Tactical Response Teams organize field operatives into specialized emergency response units attached to parent offices (MDRRMO, BFP, PNP, RHU) and staged at designated physical response bases.

## Data Structures
- `response_teams`:
  - `team_name`: Descriptive name (e.g., Alpha Rescue Unit, Paramedic Squad 1).
  - `call_sign`: Unique radio handle (e.g., ALPHA-1, MEDIC-2).
  - `team_type`: Medical, Rescue, Fire, Police, Logistics.
  - `office_id`: Foreign key to parent agency.
  - `response_base_id`: Physical staging outpost location.
  - `team_leader_id`: Foreign key to `personnel_profiles`.
  - `authorized_categories`: JSON array of incident category slugs certified for response.
- `team_memberships`: Pivot table mapping `personnel_id` to `team_id` with `role_in_team` and timestamp.
- `team_jurisdictions`: Pivot table mapping `team_id` to `barangay_id` for geographic coverage.

