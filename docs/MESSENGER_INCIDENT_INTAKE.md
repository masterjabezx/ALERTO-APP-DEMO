# Facebook Messenger Automated Incident Intake Workflow

## 1. Automated Reporting State Machine

When a citizen contacts the Hinoba-an LDRRMO Facebook Page, the conversational assistant guides them step-by-step through an emergency reporting intake:

```
[Start / Greeting]
        │ (Keywords: report, emergency, saklolo, help OR Menu Tap)
        ▼
[Step 1: awaiting_category]
   ├── Prompts Quick Reply options with active incident categories (Flood, Fire, Medical, etc.)
   └── Updates intake category
        │
        ▼
[Step 2: awaiting_location]
   ├── Prompts citizen for exact Barangay, Sitio, or Street address
   └── Prompts GPS location share or maps pin
        │
        ▼
[Step 3: awaiting_phone]
   ├── Validates Philippine mobile number format (09XXXXXXXXX)
   └── If invalid, politely requests correction
        │
        ▼
[Step 4: awaiting_attachments / ready_for_review]
   ├── Ingests photos/videos as evidence attachments
   └── Marks intake status as ready_for_review
```

---

## 2. Incomplete Report Persistence
Even if a citizen disconnects or stops replying halfway through the workflow:
- The intake state is preserved in `incident_intake_records` with status `incomplete`.
- The conversation and all captured fragments (text, coordinates, preliminary numbers) are visible in the Admin Messenger Desk.
- Dispatchers can reach out directly via Messenger or the extracted phone number to follow up.

---

## 3. Human Review & Conversion to Formal Incident

All incident reports arriving from Facebook Messenger **MUST be verified by an Admin dispatcher**.

### Conversion Rules:
1. **Initial Incident Status:** Always created in `pending_review` status. Auto-dispatch is forbidden.
2. **Reference Number Format:** Uses standard sequential numbering with an explicit `-FB` suffix:
   `INC-YYYY-XXXX-FB` (e.g., `INC-2026-0042-FB`).
3. **Source Attribution:** The incident `source` field is permanently recorded as `facebook_messenger`.
4. **Linkage:** The intake record is linked to the resulting `incidents.id` and marked as `converted`.
5. **Media Transfer:** All downloaded photos and videos attached to the Messenger intake are converted to permanent `incident_media` records linked to the new incident.
6. **Broadcasting:** Emits `IncidentIntakeUpdated` on `admin.facebook-messenger` and `admin.operations` to notify dispatchers in real-time.
