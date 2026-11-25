# ALERTO APP — Geographic Data Implementation & Boundary Validation

## 1. Municipal Geography of Hinoba-an

The Municipality of Hinoba-an, Negros Occidental comprises **13 administrative barangays**:
1. Alim
2. Asia
3. Bacuyangan
4. Bulwangan
5. Culipapa
6. Damutan
7. Daug
8. Po-ok
9. San Rafael
10. Sangke
11. Talacagay
12. Poblacion I
13. Poblacion II

---

## 2. Point-in-Polygon & Ray-Casting Algorithm

Instead of relying solely on reverse-geocoded textual street addresses, ALERTO APP verifies incident locations using **Ray-Casting Point-in-Polygon GIS algorithms** (`App\Services\GeoService`):

```
Algorithm: Ray-Casting Point-in-Polygon
Input: Point P (latitude, longitude), Polygon Ring vertices V[0...n-1]
Output: Boolean (True if inside, False otherwise)

1. Set inside = false
2. For each edge (V[i], V[j]):
   If (V[i].lat > P.lat) != (V[j].lat > P.lat) AND
      (P.lng < (V[j].lng - V[i].lng) * (P.lat - V[i].lat) / (V[j].lat - V[i].lat) + V[i].lng):
         Toggle inside = !inside
3. Return inside
```

### Bounding-Box Pre-Filtering
Before evaluating ray-casting intersections across thousands of polygon coordinates, `GeoService::identifyBarangay` evaluates bounding box boundaries indexed directly in MariaDB (`bounding_min_lat`, `bounding_max_lat`, `bounding_min_lng`, `bounding_max_lng`), achieving sub-millisecond GIS resolution.

---

## 3. Boundary Management Console (`/admin/map/boundaries`)

System Administrators can import verified GeoJSON boundary datasets from:
- **NAMRIA** (National Mapping and Resource Information Authority)
- **LGU MPDO** (Municipal Planning and Development Office)

### Supported Import Properties:
- Municipal boundary polygon.
- Individual barangay boundary polygons.
- Source citation and verification metadata (`source_information`, `is_verified`, `imported_at`).
- Automatic bounding box computation upon import.

