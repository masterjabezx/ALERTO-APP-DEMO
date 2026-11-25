# Incident Evidence Viewer & Live Operations Map Integration

## 1. Admin Evidence Viewer (`ADMIN → INCIDENT MANAGEMENT → EVIDENCE VIEWER`)
- **Route:** `GET /admin/incidents/evidence` (`admin.incidents.evidence`)
- **View:** [resources/views/admin/incidents/evidence.blade.php](file:///c:/Users/ACER%20NITRO/Desktop/JABEZ%20DEV%20FILES/WEB/ALERTO%20APP/resources/views/admin/incidents/evidence.blade.php)

### Capabilities
- Displays verified photo thumbnails and video cards across all incident-linked media.
- Full multi-criteria filtering:
  - Incident reference search
  - Incident category
  - Hinoba-an Barangay
  - Uploader & role
  - Media type (`photo` / `video`)
  - Geo provenance (`location_source`)
  - Submission date range (`date_from`, `date_to`)
- **Interactive Forensic Inspector Modal (GSAP-enhanced):**
  - Renders full-size high-resolution photos or native HTML5 `<video controls>` playback via authorized private streaming (`route('media.stream', id)`).
  - Displays Capture GPS vs Upload GPS vs Inherited GPS, GPS accuracy (`±m`), Barangay, Camera Make/Model, File Size, and live SHA-256 verification status.
  - Provides direct deep-link action **Open Evidence on Municipal Map** (`/admin/map?focus_evidence={id}&lat={lat}&lng={lng}`).

---

## 2. Live Operations Map Integration (`STEP 13 + STEP 16`)
- **Admin Live Map:** Displays geo-tagged evidence markers distinctly separated from main incident pins (never moving incident pins when evidence originates elsewhere):
  - **Photo Evidence (`photo_evidence`):** Violet badge pin (`P`)
  - **Video Evidence (`video_evidence`):** Cyan badge pin (`V`)
  - **Personnel-Uploaded Evidence (`personnel_evidence`):** Emerald badge pin (`R`)
  - **Office-Uploaded Evidence (`office_evidence`):** Amber badge pin (`O`)
- **Clicking any Evidence Marker** opens a dedicated side card containing:
  - Media preview (`<img>` / `<video>`)
  - Related incident reference, source channel, uploader & account type
  - Capture timestamp, upload timestamp, coordinates, accuracy, and `location_source`
  - Explicit action buttons: **OPEN RELATED INCIDENT** and **VIEW ORIGINAL MEDIA**
- **Role-Scoped Map Isolation:**
  - **Office Map (`/office/map/data`):** Returns `evidence` markers strictly scoped to incidents assigned to or handled by that partner office.
  - **Personnel Map (`/personnel/map/data`):** Returns `evidence` markers strictly scoped to incidents assigned to that responder/team or active jurisdiction.

