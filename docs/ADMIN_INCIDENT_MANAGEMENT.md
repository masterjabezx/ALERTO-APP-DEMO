# ALERTO APP — Admin Incident Management Architecture

## 1. Overview
The **Admin Incident Management** module serves as the central command, triage, routing, and dispatch control system for the **Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO)** Emergency Operations Center (EOC).

It manages the entire lifecycle of emergency incidents submitted by citizens or external channels, providing real-time data metrics, multi-level verification, automatic jurisdictional routing, manual response team assignments, and official emergency dispatch mobilization.

---

## 2. Navigation & 8 Incident Sub-Sections
Accessible via **Admin → Incident Management** (`/admin/incidents`), the interface provides 8 purpose-built operational tabs:

| Tab ID | Section Name | Target State | Operational Purpose |
|---|---|---|---|
| `overview` | **Incident Overview** | Active & Pending | Default high-level operational overview across active and pending incidents |
| `pending_review` | **Pending Review** | `pending_review` | Human triage queue; calls awaiting intake verification, callback, and decision |
| `accepted` | **Accepted Incidents** | `accepted` | Reports verified and confirmed; ready for response unit assignment and dispatch |
| `dispatched` | **Dispatched Incidents** | `dispatched` | Response teams officially mobilized and en route to the incident coordinates |
| `active` | **Active Incidents** | `acknowledged`, `on_the_way`, `on_scene`, `in_progress` | Field units currently engaging on-scene or rendering emergency assistance |
| `resolved` | **Resolved Incidents** | `resolved` | Emergency mitigated, patients transferred, or threat neutralized |
| `rejected` | **Rejected Incidents** | `rejected` | Malicious, duplicate, or unverified reports preserved with required justification |
| `all` | **All Incident Records** | All Statuses | Comprehensive searchable master archive for compliance and post-incident analysis |

---

## 3. Search, Filter & Sizing Engine
The searchable table supports multi-parameter filtering:
- **Search Query:** Matches reference number (`INC-...`), caller name, caller phone number, or incident description.
- **Category:** Filters by any of the 9 official categories (Fire Incident, Medical Emergency, Road Accident, Flooding, Landslide, Rescue Needed, Missing Person, Fallen Tree, Other Incident).
- **Hinoba-an Barangay:** Filters across all 13 official municipal barangays.
- **Priority:** `Critical`, `High`, `Medium`, `Low`.
- **Channel:** `citizen_portal`, `facebook_messenger`, `admin_encoded`.
- **Contact Verification:** `verified`, `verification_pending`, `failed_verification`, `unverified`.
- **Assigned Response Office:** PNP, BFP, CDRRMO, CHO/EMS.
- **Date Range:** `date_from` and `date_to` bounds against `submitted_at`.
- **Sorting:** Multi-directional sort on `submitted_at`, `reference_number`, `priority`, and `status`.

---

## 4. Live Operations Integration
Statistics displayed on the Admin Dashboard and Incident Management toolbar are calculated directly from real database records using aggregated queries:
- Total recorded incidents
- Real-time status counts (`pending_review`, `accepted`, `dispatched`, `active`, `resolved`, `rejected`)
- Critical and High priority counts
- Barangay geographic distribution
- Category breakdown
- 7-day rolling intake trends

