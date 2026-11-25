# ALERTO APP — Office Jurisdictions & Territorial Coverage

## 1. Overview & Municipal Geography

The Municipality of Hinoba-an, Negros Occidental consists of **13 administrative barangays** spread along coastal and mountainous topography.

To ensure rapid response times, prevent overlapping dispatches, and optimize mutual aid, the ALERTO APP enforces precise territorial jurisdiction bindings between emergency response agencies and municipal barangays.

---

## 2. Municipal Barangays Coverage Matrix

| Barangay Name | Classification | Primary Agencies Bound | Staging Bases / Facilities |
|---|---|---|---|
| **Alim** | Coastal / Rural | PNP, BFP, LDRRMO, PCG | Alim Coastal Substation |
| **Asia** | Rural / Agricultural | PNP, LDRRMO, RHU | Asia Community Health Post |
| **Bacuyangan** | Coastal / Urbanizing | PNP, BFP, LDRRMO, RHU | Bacuyangan Quick Response Outpost |
| **Bulwangan** | Rural / Mountainous | LDRRMO Mountain Rescue, PNP | Bulwangan Relay Station |
| **Culipapa** | Coastal / Highway | PNP Highway Patrol, BFP, RHU | Culipapa Emergency Staging |
| **Damutan** | Upland / Remote | LDRRMO Wildland Unit, PNP | Damutan Outpost |
| **Daug** | Coastal / Estuary | PCG Coastal Unit, LDRRMO, PNP | Daug Marine Staging |
| **Po-ok** | Rural / Agricultural | PNP, LDRRMO, RHU | Po-ok Agro-Response Unit |
| **San Rafael** | Coastal / Rural | PNP, BFP, LDRRMO | San Rafael Detachment |
| **Sangke** | Coastal / Border | PCG, PNP Maritime, LDRRMO | Sangke Border Command |
| **Talacagay** | Coastal / Fishery | PCG, PNP, RHU, LDRRMO | Talacagay Staging Area |
| **Poblacion I** | Urban / Commercial Center | BFP Central, PNP HQ, RHU Main, LDRRMO EOC | Central Emergency Complex |
| **Poblacion II** | Urban / Civic Center | BFP Central, PNP HQ, RHU Main, LDRRMO EOC | Hinoba-an Municipal Hall Staging |

---

## 3. Database Architecture & Relationships

```
┌─────────────────────┐             ┌─────────────────────────┐
│       offices       │ 1         * │   office_jurisdictions  │
│---------------------├────────────►│-------------------------│
│ id                  │             │ office_id               │
│ name                │             │ barangay_id             │
│ code                │             └────────────┬────────────┘
└─────────────────────┘                          │
                                                 │ *
                                                 ▼ 1
                                    ┌─────────────────────────┐
                                    │        barangays        │
                                    │-------------------------│
                                    │ id                      │
                                    │ name                    │
                                    │ code                    │
                                    └─────────────────────────┘
```

---

## 4. Operational Dispatch & Routing Integration

1. **Automatic Routing Evaluation**:
   When an incident is reported, `IncidentRoutingService` inspects the incident's `barangay_id` and `category_id`.
2. **Jurisdiction Matching**:
   Agencies with registered `office_jurisdictions` for that barangay are matched against active `incident_routing_rules`.
3. **Mutual Aid & Border Escapes**:
   If an incident occurs in a remote barangay where the primary agency's stationed unit is unavailable (`busy` or `off_duty`), the system automatically recommends the nearest adjacent agency team.
4. **Administrative Overrides**:
   System Administrators retain real-time authority at `/admin/personnel/jurisdictions` and `/admin/offices` to modify agency territorial boundaries during severe typhoons or disaster declarations.

