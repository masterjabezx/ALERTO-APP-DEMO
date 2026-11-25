# ALERTO APP — Personnel & Team Jurisdictions

## Overview
Hinoba-an covers 13 distinct barangays. The jurisdiction subsystem ensures equitable and certified geographic allocation of field operatives and response teams.

## Hinoba-an 13 Barangays Coverage
1. Alim
2. Asia
3. Bacuyangan
4. Barangay I (Poblacion)
5. Barangay II (Poblacion)
6. Bulwangan
7. Culipapa
8. Damutan
9. Daug
10. Po-ok
11. San Rafael
12. Sangke
13. Talacagay

## Jurisdiction Mapping
- Operatives are mapped to barangays via `personnel_jurisdictions` (`is_primary` flag designates primary territorial station).
- Teams are mapped to barangays via `team_jurisdictions`.
- The Incident Routing Engine prioritizes personnel and teams stationed in or assigned to the incident scene barangay.

