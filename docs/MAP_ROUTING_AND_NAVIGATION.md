# ALERTO APP — Map Routing, Navigation & Directions

## 1. Tactical Route Navigation

The ALERTO APP routing engine (`App\Services\GeoService` and `App\Http\Controllers\Map\RouteNavigationController`) calculates road distance, estimated travel duration (ETA), and polyline waypoints connecting emergency responders to incident scenes.

---

## 2. Calculation Mechanics

### Haversine Road Metric Calculation
```php
$distanceKm = $geoService->haversineDistance($originLat, $originLng, $destLat, $destLng);
$durationMinutes = max(1, (int) round(($distanceKm / 45.0) * 60)); // Calculated using emergency vehicle velocity profile
```

### Route Navigation API Endpoint
- **Route**: `POST /map/route` or `GET /map/route`
- **Request Parameters**:
  - `origin_lat`, `origin_lng`
  - `dest_lat`, `dest_lng`
- **Response Structure**:
```json
{
  "status": "success",
  "data": {
    "distance_km": 4.82,
    "duration_minutes": 6,
    "waypoints": [
      {"lat": 9.4975, "lng": 122.5850},
      {"lat": 9.5100, "lng": 122.5900},
      {"lat": 9.5250, "lng": 122.5950}
    ],
    "google_maps_url": "https://www.google.com/maps/dir/?api=1&origin=9.497500,122.585000&destination=9.525000,122.595000&travelmode=driving"
  }
}
```

---

## 3. External Navigation App Integration

Field personnel can launch native Google Maps, Apple Maps, or Waze turn-by-turn voice navigation directly from their device by clicking the **"Start Turn-by-Turn GPS Navigation"** button generated on their assigned incident cards.

