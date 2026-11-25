# Facebook Messenger Location Handling & Geo-Tagging

## 1. Dual Location Ingestion Strategy

Emergency incident locations arriving via Facebook Messenger are captured using two complementary sources:

### A. Explicit Native GPS Location Share (`messenger_gps`)
When a citizen taps the Messenger location pin or sends a location attachment:
- The webhook payload delivers explicit decimal coordinates:
  ```json
  "attachments": [{
      "type": "location",
      "payload": {
          "coordinates": {
              "lat": 9.4975,
              "long": 122.5850
          }
      }
  }]
  ```
- Coordinates are extracted and stored in `incident_intake_records.collected_latitude` and `collected_longitude`.
- The location source is explicitly set to `messenger_gps`.
- Automatically reverse-geocodes or identifies the corresponding Hinoba-an Barangay boundary.

### B. Conversational Text Description (`text_extracted`)
When a citizen enters a street address, landmark, or Sitio name:
- The text is saved in `incident_intake_records.location_details`.
- The location source is flagged as `text_extracted`.
- The Admin conversion interface enables dispatchers to refine the pin on the interactive Hinoba-an Operations Map before finalizing the incident.

---

## 2. Municipal Boundary & Fallback Coordinates
For reports where only a text description is provided:
- If a valid Hinoba-an Barangay name is identified (e.g. *Bacuyangan*, *Poblacion*, *Culipapa*, *Damutan*), default center coordinates for that Barangay are assigned as a baseline.
- If no coordinates can be determined, default Hinoba-an town center coordinates (`9.4975`, `122.5850`) are populated, and the dispatcher is prompted to set the pin during review.

