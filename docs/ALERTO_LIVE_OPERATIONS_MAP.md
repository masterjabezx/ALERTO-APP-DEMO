# ALERTO APP — Live Operations Map Architecture & Operational Guide

## 1. Executive Overview

The **ALERTO LIVE OPERATIONS MAP** is a centralized, mission-critical geographic monitoring platform engineered specifically for the Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO) Emergency Operations Center (EOC).

The platform unifies multi-source incident reporting, field responder GPS telemetry, agency staging facilities, geo-tagged multimedia evidence, and territorial barangay boundary polygons onto an interactive, real-time spatial canvas.

---

## 2. Multi-Role Spatial Access Architecture

```
                             ┌──────────────────────────────┐
                             │       ALERTO LIVE GIS        │
                             │ Central Spatial Data Engine  │
                             └──────────────┬───────────────┘
                                            │
                    ┌───────────────────────┼───────────────────────┐
                    │                       │                       │
         ┌──────────▼──────────┐ ┌──────────▼──────────┐ ┌──────────▼──────────┐
         │      ADMIN MAP      │ │     OFFICE MAP      │ │    PERSONNEL MAP    │
         │ `/admin/map`        │ │ `/office/map`       │ │ `/personnel/map`    │
         │ - Complete Municipal│ │ - Scoped to Agency  │ │ - Active Scene Targets│
         │   Incident Scope    │   Assignments & Teams │ - Jurisdiction Bound  │
         │ - All Responders    │ - Agency Responders   │ - GPS Duty Broadcast  │
         │ - GeoJSON Admin     │ - Base Facilities     │ - Navigation Driving  │
         │ - In-Map Review     │ - Scoped Routes       │   Directions          │
         └─────────────────────┘ └─────────────────────┘ └─────────────────────┘
```

---

## 3. Incident Marker Visual Standards

The map enforces unambiguous visual markers combining custom color tokens and SVG icons:

| Status | Color Code | Hex Token | Icon Graphic | Operational Meaning |
|---|---|---|---|---|
| **Pending Review** | Orange | `#F59E0B` | `clock` (pulsing) | Newly submitted; awaiting EOC triage review |
| **Accepted** | Blue | `#3B82F6` | `check-circle` | Verified & approved; ready for dispatch |
| **Dispatched** | Green | `#10B981` | `send` | Unit dispatched; responders alerted |
| **Acknowledged** | Dark Green | `#059669` | `badge-check` | Responder received and accepted dispatch |
| **On The Way** | Green | `#10B981` | `navigation` | Units en route to target emergency scene |
| **On Scene** | Deep Green | `#047857` | `map-pin` | Units arrived at scene; operations active |
| **In Progress** | Green | `#10B981` | `activity` | Suppression, rescue, or treatment underway |
| **Resolved** | Gray | `#6B7280` | `check` | Hazard mitigated; scene stabilized |
| **Closed** | Dark Gray | `#4B5563` | `archive` | Post-incident administrative logging complete |
| **Rejected** | Red | `#EF4444` | `x-circle` | Hoax, duplicate, or out-of-jurisdiction report |

---

## 4. Operational Invariant: Acceptance ≠ Dispatch

In emergency incident response workflows, **Incident Acceptance is strictly separated from Dispatch**:
1. When a Citizen submits an emergency report, the map displays a **Pending Review** pin (orange, pulsing).
2. The Administrator or authorized Office Admin inspects details and **Accepts** the incident.
3. The pin turns **Blue** (`accepted`). Routing recommendations and assignment options become available.
4. The Admin confirms assignments to an agency, team, or responder.
5. The Admin authorizes **Emergency Dispatch**. Only then does the pin turn **Green** (`dispatched`), and only confirmed assignees receive dispatch notifications and route navigation access.

