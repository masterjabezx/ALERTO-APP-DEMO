# ALERTO APP — Complete UI/UX Design System

## 1. Visual Identity & Brand Philosophy

- **Application Name:** ALERTO APP
- **Agency:** Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO)
- **Mission:** High-reliability emergency response, seamless multi-agency incident intake, real-time responder dispatch telemetry, and accessible citizen communication.

---

## 2. Color Tokens & Semantic Hierarchy

| Token Name | Hex Code | Semantic Role | Tailwind / CSS Variable |
|---|---|---|---|
| **Primary Navy** | `#173957` | Executive headers, EOC command sidebar, primary action buttons | `--color-alerto-navy`, `bg-[#173957]` |
| **Primary Blue** | `#3187D8` | Interactive accents, active navigation indicators, operational links | `--color-alerto-blue`, `text-[#3187D8]` |
| **Emergency Red** | `#DB4545` | Critical alerts, SOS hotlines, emergency dispatch actions | `--color-alerto-red`, `bg-[#DB4545]` |
| **Success Green** | `#19865B` | Online responder readiness, resolved incidents, safe status | `--color-alerto-green`, `bg-[#19865B]` |
| **Warning Amber** | `#F0A82E` | Ongoing triage, unverified reports, weather warning advisories | `--color-alerto-amber`, `bg-[#F0A82E]` |
| **Background** | `#F5F7FA` | Clean, low-fatigue application canvas | `--color-alerto-bg`, `bg-[#F5F7FA]` |
| **Surface** | `#FFFFFF` | Major card containers, modals, and data panels | `--color-alerto-surface`, `bg-white` |

---

## 3. Elevation & Corner Radius System

- **Corner Radii:**
  - Standard Major Cards: `20px` (`rounded-[20px]`, `--radius-card`)
  - Hero Banners & Modals: `24px` (`rounded-[24px]`, `--radius-card-lg`)
  - Sub-elements & Form Inputs: `12px` - `16px` (`rounded-xl`)
  - Pill Badges: `9999px` (`rounded-full`)
- **Soft Shadows:**
  - Card Shadow: `0 4px 20px -2px rgba(23, 57, 87, 0.06)`
  - Card Hover Shadow: `0 12px 28px -4px rgba(23, 57, 87, 0.12)`
  - Modal / Elevated Popups: `0 20px 40px -8px rgba(23, 57, 87, 0.25)`

---

## 4. Typography Scale

- **Primary Font Family:** Plus Jakarta Sans
- **Tactical / Coordinates Font Family:** JetBrains Mono
- **Scale:**
  - H1 Display: `2rem - 2.5rem` (32px - 40px), font-black, tracking-tight
  - Section Headings: `1.25rem - 1.5rem` (20px - 24px), font-extrabold
  - Card Titles: `0.875rem - 1rem` (14px - 16px), font-bold
  - Body Copy: `0.875rem` (14px), font-medium, leading-relaxed
  - Data / Status Badges / Metadata: `0.6875rem - 0.75rem` (11px - 12px), uppercase tracking-wider

---

## 5. Portal Visual & Interaction Guidelines

### A. Citizen Portal (Mobile-First, Minimal Typing)
- **Navigation:** Exclusively 3 primary views:
  1. *Submit Incident*
  2. *Incident Record*
  3. *Contact Numbers*
- **Emergency Action:** Persistent **DIAL DAPH COMMAND CENTER** banner with 1-tap dialer.
- **Form Design:** Large tap-friendly category cards, mandatory location pin, instant camera photo/video upload, zero visual clutter.

### B. Personnel Portal (Field Operations & Speed)
- **Navigation:** 5 main views:
  1. *Tasks*
  2. *Active Incidents*
  3. *Live Incident Map*
  4. *Records & Incidents*
  5. *Messages*
- **Readiness:** Prominent ONLINE / OFFLINE availability badge with instant toggle.

### C. Office Portal (Coordination Desk)
- **Navigation:** 5 main views:
  1. *Office Dashboard*
  2. *Incident Monitoring*
  3. *Office Incident Map*
  4. *Records & Reports*
  5. *Messages*
- **Features:** Comprehensive tables with status badges, search and filter drawers, agency coordination view.

### D. Admin Portal (Emergency Operations Center - EOC)
- **Navigation:** Full 12-item command navigation with collapsible `#173957` dark navy sidebar.
- **Features:** High-density GIS map container, live telemetry status, real-time dispatch feed, GSAP count-up numbers, multi-agency audit trails.

