# ALERTO APP — Citizen Portal Architecture & Guidelines

## 1. Overview & Purpose
The **Citizen Portal** of ALERTO APP is the dedicated, mobile-first public emergency intake and tracking interface for the residents of the Municipality of Hinoba-an, Negros Occidental, operated in coordination with the Hinoba-an Local Disaster Risk Reduction and Management Office (LDRRMO).

The interface is engineered around one core operational reality: in an emergency, citizens require speed, clarity, and zero cognitive friction. The interface is strictly streamlined and stripped of administrative overhead, complex charts, or deep nested menus.

---

## 2. Navigation Architecture (Strict Rule of Three)
The Citizen Portal is strictly restricted to **ONLY THREE MAIN MENU ITEMS**:

```
+-------------------------------------------------------------+
|                      ALERTO CITIZEN                         |
|  [SUBMIT INCIDENT]    [INCIDENT RECORD]    [CONTACT NUMBERS]|
+-------------------------------------------------------------+
```

1. **SUBMIT INCIDENT (`/citizen/dashboard`):**
   - The primary action hub.
   - Immediate 5-step incident intake form.
   - Quick category selection across 9 official emergency types.
   - Device GPS acquisition with manual pin fallback.
   - Photo/video evidence upload with SHA256 checksums and geo-tagging.
   - Prominent DIAL DAPH COMMAND CENTER emergency hotline banner with one-tap `tel:` dialing.

2. **INCIDENT RECORD (`/citizen/records`):**
   - Personal tracking center for the authenticated citizen.
   - Filterable view across all statuses: All, Pending, Accepted, Dispatched, In Progress, Resolved, Rejected.
   - Secure record detail (`/citizen/records/{reference}`) showing chronological milestone timeline, geo-tagged photo evidence, and public response remarks.
   - Strict query isolation (`where('reporter_id', Auth::id())`); citizens can never view or enumerate other citizens' reports.

3. **CONTACT NUMBERS (`/citizen/contacts`):**
   - Comprehensive emergency directory for Hinoba-an municipality.
   - Featured DIAL DAPH COMMAND CENTER hotline (`0917-123-4567`).
   - Municipal response agencies: Hinoba-an LDRRMO, Bureau of Fire Protection (BFP Hinoba-an), Philippine National Police (PNP Hinoba-an), Rural Health Unit (RHU Hinoba-an), Coast Guard Sub-Station Hinoba-an.
   - Direct link to official LDRRMO Facebook page.

---

## 3. UI/UX & Mobile-First Standards
- **Design Tokens:**
  - Emergency Red: `#DB4545` (primary submit button, alert banners)
  - Primary Navy: `#173957` (headers, navigation, authority branding)
  - Primary Blue: `#3187D8` (accents, interactive elements)
  - Success Green: `#19865B` (verified GPS indicators, accepted status)
  - Warning Amber: `#F0A82E` (pending review, manual pin notices)
  - Background: `#F5F7FA` | Surface: `#FFFFFF`
- **Card Corners:** Rounded between 18px and 24px (`rounded-2xl` / `rounded-3xl`).
- **Touch Targets:** Minimum 48px height on all touch targets for one-handed thumb use on mobile devices.
- **Micro-Interactions:** GSAP entrance animations, Alpine.js reactive state binding, responsive sticky bottom navigation bar on mobile screens.

---

## 4. Architectural Separation
- **Separation of Concerns:**
  - Route declarations: `routes/web.php` under `auth`, `account.type:citizen`, `account.active`.
  - HTTP Handling: `app/Http/Controllers/Citizen/CitizenPortalController.php`.
  - Form Validation: `app/Http/Requests/Citizen/SubmitIncidentRequest.php`.
  - Business & GIS Logic: `app/Services/CitizenIncidentService.php`.
  - Blade Layouts: `resources/views/layouts/citizen.blade.php` and `resources/views/components/layouts/citizen.blade.php`.
  - Views: `resources/views/dashboards/citizen.blade.php`, `resources/views/citizen/success.blade.php`, `resources/views/citizen/records.blade.php`, `resources/views/citizen/record-detail.blade.php`, `resources/views/citizen/contacts.blade.php`.

