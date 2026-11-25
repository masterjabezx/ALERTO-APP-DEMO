# ALERTO APP — GPS Location System & Boundary Diagnostics

## 1. Overview
The GPS Location System in ALERTO APP bridges device-level browser hardware telemetry with the municipal GIS boundary of Hinoba-an, Negros Occidental. The system guarantees that every incident has spatial coordinates while tracking the provenance and precision of the data.

---

## 2. Location Source Provenance
The system records how coordinates were obtained via the `location_source` field in `incident_locations`:

| Source Value | Meaning | Typical Accuracy | Verification Level |
|---|---|---|---|
| `gps_device` | Acquired directly from hardware GPS / Browser Geolocation API | 5m – 25m | High (Hardware Verified) |
| `map_picker` | Manually pinned or dragged by user on interactive map canvas | Visual | Medium (User Confirmed) |
| `fb_shared_location` | Coordinates ingested from Facebook Messenger location share | Variable | Medium (Messenger Shared) |
| `admin_entered` | Manually transcribed by LDRRMO call taker from landline call | Approximated | Operator Verified |

---

## 3. Municipal Boundary Verification (Hinoba-an)
Hinoba-an is situated at the southwestern tip of Negros Occidental, bounded by the Sulu Sea to the west and south, Sipalay City to the north, and Negros Oriental to the east.

ALERTO APP enforces a municipal bounding box check via `CitizenIncidentService::isCoordinateWithinHinobaan()`:

```php
public function isCoordinateWithinHinobaan(float $lat, float $lng): bool
{
    $minLat = (float) SystemSetting::get('municipal_boundary_lat_min', 9.4000);
    $maxLat = (float) SystemSetting::get('municipal_boundary_lat_max', 9.7500);
    $minLng = (float) SystemSetting::get('municipal_boundary_lng_min', 122.3500);
    $maxLng = (float) SystemSetting::get('municipal_boundary_lng_max', 122.6500);

    return $lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng;
}
```

### Boundary Handling:
- **Within Boundary:** Standard intake processing.
- **Outside Boundary:** The incident is still accepted to preserve life and safety (e.g., offshore maritime emergencies, borderline highway collisions with neighboring Sipalay or Basay), but automatically flagged:
  `[REVIEW REQUIRED: Coordinates flagged outside standard Hinoba-an municipal boundary]`
  This alerts the LDRRMO intake officer to coordinate with neighboring municipal emergency operations centers.

---

## 4. Barangay Fallback System
When device GPS is disabled or denied:
1. The user selects their incident barangay from the 13 Hinoba-an barangays:
   - Alim, Asia, Bacuyangan, Barangay I (Poblacion), Barangay II (Poblacion), Bulwangan, Culipapa, Damutan, Daug, Po-ok, San Rafael, Sangke, Talacagay.
2. The system pre-fills coordinates from `barangays.center_latitude` and `barangays.center_longitude`.
3. The user adjusts the pin to the specific sitio, street, or landmark.
4. The location source is marked as `map_picker`.

