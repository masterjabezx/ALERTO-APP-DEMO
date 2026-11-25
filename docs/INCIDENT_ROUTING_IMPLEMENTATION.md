# ALERTO APP — Incident Routing Engine Implementation

## 1. Overview
The ALERTO APP **Incident Routing Engine** evaluates accepted incidents against configured administrative rules, geographic jurisdictions, and live personnel readiness to recommend the most qualified response agency and team.

---

## 2. Automatic Routing Factors
The engine evaluates the following factors in priority order:
1. **Incident Category:** Matches master category (e.g. *Fire Incident*, *Medical Emergency*, *Flooding*, *Road Accident*).
2. **Barangay Jurisdiction:** Evaluates barangay-specific rules first; falls back to category-wide municipal defaults if no barangay override exists.
3. **Designated Target Office:** Primary department responsible (e.g. BFP for Fire, EMS for Medical, PNP for Crime/Traffic, CDRRMO for Disaster/Rescue).
4. **Fallback Office:** Secondary agency designated if target office is overwhelmed or unavailable.
5. **Response Team:** Matches specialized response squads (e.g. *Engine Company 1*, *Rescue Squad Alpha*, *Advanced Life Support Unit*).
6. **Live Responder Availability:** Inspects `personnel_availabilities.operational_status`.
   - **Crucial Rule:** Responders who are `off_duty`, `busy`, or `dispatched` are **strictly excluded** from eligible recommendation.
7. **Active Incident Load:** Computes current active assignments per responder to prevent overloading.

---

## 3. Engine Evaluation Algorithm (`recommendRouting`)
```
                                 [Accepted Incident]
                                          │
                         Check `incident_routing_rules`
                         matching Category & Barangay
                                          │
                     ┌────────────────────┴────────────────────┐
                RULE FOUND                               NO RULE FOUND
                     │                                         │
        Target Office Assigned                     Fallback to CDRRMO /
                     │                             Disaster Coordination
                     ▼                                         │
          Identify Response Team                               ▼
                     │
          Query Personnel in Office/Team
                     │
       ┌─────────────┴─────────────┐
AVAILABLE (`operational_status = 'available'`)   OFFLINE / OFF-DUTY
       │                                               │
Added to `eligible_personnel`              Added to `offline_personnel`
       │                                               │
Count > 0 ?                                      Trigger Warning:
YES: High readiness recommendation               "All personnel currently offline;
NO:  Manual override required                     manual assignment required."
```

---

## 4. Manual Assignment & Override Capabilities
While the routing engine generates recommendations, the Admin or authorized Office Admin retains full control to:
- Accept or change the recommended Office.
- Select an alternative Response Team.
- Designate specific individual responders or assign multiple eligible personnel simultaneously.
- Input operational directives and tactical radio channels in assignment notes.
- Concurrency checks prevent duplicate active assignments on the same unit.

