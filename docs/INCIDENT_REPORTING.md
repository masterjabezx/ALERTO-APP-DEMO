# ALERTO APP — Incident Reporting Architecture & Lifecycle

## 1. Incident Submission Workflow
The citizen incident reporting workflow is built for rapid execution during life-threatening crises. Citizens complete 5 structured steps on a single streamlined page:

```
[1. Select Category] ➔ [2. Caller Phone] ➔ [3. Verify Location] ➔ [4. Details & Media] ➔ [5. Submit]
```

### The 9 Official Master Categories:
1. **Fire Incident** (Structure fire, forest fire, electrical fire)
2. **Medical Emergency** (Trauma, cardiac arrest, respiratory crisis, severe injury)
3. **Road Accident** (Vehicular collision, motorcycle crash, pedestrian incident)
4. **Flooding** (River overflow, storm surge, flash flood, impassable road)
5. **Landslide** (Soil erosion, mudslide, blocked mountain transit)
6. **Rescue Needed** (Trapped individual, coastal rescue, swift water emergency)
7. **Missing Person** (Lost child, stranded hiker, missing fisherman)
8. **Fallen Tree** (Blocked national highway, compromised power lines)
9. **Other Incident** (Hazmat hazard, public safety disturbance, structural damage)

---

## 2. Initial Status: Strictly `pending_review`
In ALERTO APP, citizen-submitted reports **NEVER** bypass human authority.

> [!WARNING]
> No citizen submission is ever automatically accepted, auto-dispatched, or marked as confirmed. All citizen submissions enter MariaDB strictly with:
> ```sql
> status = 'pending_review'
> ```

### Why `pending_review` is strictly enforced:
- **Triage & Verification:** Hinoba-an LDRRMO intake dispatchers verify incoming reports against duplicate calls, malicious prank submissions, or accidental taps before mobilizing municipal apparatus (BFP, PNP, Ambulances).
- **Caller Verification:** Dispatchers initiate rapid callback confirmation to the required caller phone (`09XXXXXXXXX`).
- **Resource Protection:** Prevents unnecessary dispatch of scarce rural emergency assets.

---

## 3. Unique Reference Number Format
Every report receives an immutable reference number generated upon database transaction commit:
```
INC-{YEAR}-{SEQUENCE}-{TOKEN}
Example: INC-2026-0001-K8F2
```
- `INC`: Incident prefix
- `YEAR`: 4-digit calendar year (`2026`)
- `SEQUENCE`: 4-digit zero-padded sequence for the year (`0001`, `0002`, ...)
- `TOKEN`: 4-character cryptographic alphanumeric string to prevent reference enumeration

---

## 4. Post-Submission Success Screen
Upon submission, the citizen is immediately redirected to `/citizen/incident-success/{reference}` featuring:
- **Prominent Emergency Banner:** "REPORT RECEIVED & UNDER REVIEW"
- **Reference Number Badge:** Displayed in large monospace font for telephone communication with dispatchers.
- **Incident Summary:** Category, Barangay, submission date/time, current status (`PENDING REVIEW`).
- **Direct Hotline Call Action:** Large, pulsing emergency button:
  ```html
  <a href="tel:0917-123-4567" class="btn btn-emergency">
      CALL DIAL DAPH COMMAND CENTER
  </a>
  ```
  Dynamically retrieved from `system_settings` (`dial_daph_command_center_hotline`).
- **Track Status Button:** Directly opens the citizen's incident record timeline.

---

## 5. Incident Record Tracking & Public Timeline
Citizens can review all past and active reports in the **Incident Record** tab (`/citizen/records`):
- Filter by status tabs: `All`, `Pending`, `Accepted`, `Dispatched`, `In Progress`, `Resolved`, `Rejected`.
- Detail View (`/citizen/records/{reference}`) displays:
  - Incident status badge with dynamic color coding.
  - Chronological status timeline (`incident_status_histories`) displaying verified milestone changes.
  - Geo-tagged photo/video attachments uploaded during intake.
  - Public remarks and resolution summaries from LDRRMO.
  - **Internal Admin Notes Exclusion:** Confidential internal dispatcher logs and tactical police/fire chatter are strictly excluded from citizen views.

