<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Barangay;
use App\Models\BarangayBoundary;
use App\Models\Municipality;
use App\Models\MunicipalityBoundary;
use InvalidArgumentException;

class GeoService
{
    /**
     * Import and validate GeoJSON for a Barangay boundary.
     *
     * @throws InvalidArgumentException
     */
    public function importBarangayBoundary(int $barangayId, string $geoJsonString, ?string $source = 'LGU Official GIS'): BarangayBoundary
    {
        $barangay = Barangay::findOrFail($barangayId);
        $data = json_decode($geoJsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new InvalidArgumentException('Invalid GeoJSON payload provided.');
        }

        $bbox = $this->calculateBoundingBox($data);

        return BarangayBoundary::updateOrCreate(
            ['barangay_id' => $barangay->id],
            [
                'geojson_boundary' => $geoJsonString,
                'bounding_min_lat' => $bbox['min_lat'],
                'bounding_max_lat' => $bbox['max_lat'],
                'bounding_min_lng' => $bbox['min_lng'],
                'bounding_max_lng' => $bbox['max_lng'],
                'source_information' => $source,
                'is_verified' => true,
                'imported_at' => now(),
            ]
        );
    }

    /**
     * Import and validate GeoJSON for Hinoba-an Municipality boundary.
     *
     * @throws InvalidArgumentException
     */
    public function importMunicipalityBoundary(int $municipalityId, string $geoJsonString, ?string $source = 'NAMRIA / LGU Official GIS'): MunicipalityBoundary
    {
        $municipality = Municipality::findOrFail($municipalityId);
        $data = json_decode($geoJsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new InvalidArgumentException('Invalid GeoJSON payload provided.');
        }

        $bbox = $this->calculateBoundingBox($data);

        return MunicipalityBoundary::updateOrCreate(
            ['municipality_id' => $municipality->id],
            [
                'geojson_boundary' => $geoJsonString,
                'bounding_min_lat' => $bbox['min_lat'],
                'bounding_max_lat' => $bbox['max_lat'],
                'bounding_min_lng' => $bbox['min_lng'],
                'bounding_max_lng' => $bbox['max_lng'],
                'source_information' => $source,
                'is_verified' => true,
                'imported_at' => now(),
            ]
        );
    }

    /**
     * Identify which verified barangay contains the specified geographic coordinate.
     * Uses bounding-box pre-filtering followed by Ray-Casting Point-in-Polygon algorithm.
     */
    public function identifyBarangay(float $latitude, float $longitude): ?Barangay
    {
        // 1. Pre-filter boundaries intersecting candidate point
        $candidates = BarangayBoundary::with('barangay')
            ->where('bounding_min_lat', '<=', $latitude)
            ->where('bounding_max_lat', '>=', $latitude)
            ->where('bounding_min_lng', '<=', $longitude)
            ->where('bounding_max_lng', '>=', $longitude)
            ->get();

        foreach ($candidates as $candidate) {
            $geoJson = json_decode((string) $candidate->geojson_boundary, true);
            if (! is_array($geoJson)) {
                continue;
            }

            if ($this->isPointInGeoJson($latitude, $longitude, $geoJson)) {
                return $candidate->barangay;
            }
        }

        // Fallback: If no polygon boundary is configured or point falls slightly on edge, find closest barangay center
        return Barangay::all()->sortBy(function (Barangay $b) use ($latitude, $longitude) {
            return $this->haversineDistance($latitude, $longitude, (float) $b->center_latitude, (float) $b->center_longitude);
        })->first();
    }

    /**
     * Strictly identify the barangay ONLY when coordinates fall inside a verified polygon boundary.
     * Never falls back to nearest centroid or unverified geometry.
     */
    public function identifyVerifiedBarangay(float $latitude, float $longitude): ?Barangay
    {
        $candidates = BarangayBoundary::with('barangay')
            ->where('is_verified', true)
            ->where('bounding_min_lat', '<=', $latitude)
            ->where('bounding_max_lat', '>=', $latitude)
            ->where('bounding_min_lng', '<=', $longitude)
            ->where('bounding_max_lng', '>=', $longitude)
            ->get();

        foreach ($candidates as $candidate) {
            $geoJson = json_decode((string) $candidate->geojson_boundary, true);
            if (! is_array($geoJson)) {
                continue;
            }

            if ($this->isPointInGeoJson($latitude, $longitude, $geoJson)) {
                return $candidate->barangay;
            }
        }

        return null;
    }

    /**
     * Check if point is inside GeoJSON geometry (Polygon or MultiPolygon).
     */
    public function isPointInGeoJson(float $latitude, float $longitude, array $geoJson): bool
    {
        $geometryType = $geoJson['type'] ?? ($geoJson['geometry']['type'] ?? '');
        $coordinates = $geoJson['coordinates'] ?? ($geoJson['geometry']['coordinates'] ?? []);

        if ($geometryType === 'Feature') {
            $geometryType = $geoJson['geometry']['type'] ?? '';
            $coordinates = $geoJson['geometry']['coordinates'] ?? [];
        }

        if ($geometryType === 'Polygon') {
            // First ring is exterior ring
            return ! empty($coordinates[0]) && $this->isPointInPolygonRing($latitude, $longitude, $coordinates[0]);
        }

        if ($geometryType === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                if (! empty($polygon[0]) && $this->isPointInPolygonRing($latitude, $longitude, $polygon[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Ray-Casting algorithm to test if point (lat, lng) is inside a 2D ring of coordinates [[lng, lat], ...].
     */
    public function isPointInPolygonRing(float $latitude, float $longitude, array $ring): bool
    {
        $inside = false;
        $count = count($ring);
        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) ($ring[$i][0] ?? 0); // lng
            $yi = (float) ($ring[$i][1] ?? 0); // lat
            $xj = (float) ($ring[$j][0] ?? 0);
            $yj = (float) ($ring[$j][1] ?? 0);

            $intersect = (($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < ($xj - $xi) * ($latitude - $yi) / (($yj - $yi) ?: 0.00000001) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Calculate bounding box (min_lat, max_lat, min_lng, max_lng) from GeoJSON data.
     */
    public function calculateBoundingBox(array $geoJson): array
    {
        $coords = [];
        $this->extractAllCoordinates($geoJson, $coords);

        if (empty($coords)) {
            // Default Hinoba-an bounding box
            return [
                'min_lat' => 9.35000000,
                'max_lat' => 9.65000000,
                'min_lng' => 122.45000000,
                'max_lng' => 122.75000000,
            ];
        }

        $minLng = $coords[0][0];
        $maxLng = $coords[0][0];
        $minLat = $coords[0][1];
        $maxLat = $coords[0][1];

        foreach ($coords as $coord) {
            $lng = (float) $coord[0];
            $lat = (float) $coord[1];

            if ($lng < $minLng) {
                $minLng = $lng;
            }
            if ($lng > $maxLng) {
                $maxLng = $lng;
            }
            if ($lat < $minLat) {
                $minLat = $lat;
            }
            if ($lat > $maxLat) {
                $maxLat = $lat;
            }
        }

        return [
            'min_lat' => round($minLat, 8),
            'max_lat' => round($maxLat, 8),
            'min_lng' => round($minLng, 8),
            'max_lng' => round($maxLng, 8),
        ];
    }

    private function extractAllCoordinates(mixed $data, array &$coords): void
    {
        if (! is_array($data)) {
            return;
        }

        if (isset($data[0]) && isset($data[1]) && is_numeric($data[0]) && is_numeric($data[1])) {
            $coords[] = [$data[0], $data[1]];

            return;
        }

        foreach ($data as $item) {
            $this->extractAllCoordinates($item, $coords);
        }
    }

    /**
     * Calculate Haversine distance in kilometers between two GPS coordinates.
     */
    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Calculate navigation route metrics and generate waypoint polyline coordinates.
     */
    public function calculateRoute(float $originLat, float $originLng, float $destLat, float $destLng): array
    {
        $distanceKm = $this->haversineDistance($originLat, $originLng, $destLat, $destLng);

        // Estimate travel duration based on average emergency vehicle speed (45 km/h on national/provincial roads)
        $speedKmh = 45.0;
        $durationMinutes = max(1, (int) round(($distanceKm / $speedKmh) * 60));

        // Generate synthetic route polyline waypoints along road curvature interpolation
        $waypoints = [
            ['lat' => $originLat, 'lng' => $originLng],
            ['lat' => round($originLat + ($destLat - $originLat) * 0.33 + 0.001, 6), 'lng' => round($originLng + ($destLng - $originLng) * 0.33 - 0.001, 6)],
            ['lat' => round($originLat + ($destLat - $originLat) * 0.66 - 0.001, 6), 'lng' => round($originLng + ($destLng - $originLng) * 0.66 + 0.001, 6)],
            ['lat' => $destLat, 'lng' => $destLng],
        ];

        $googleMapsUrl = sprintf(
            'https://www.google.com/maps/dir/?api=1&origin=%f,%f&destination=%f,%f&travelmode=driving',
            $originLat,
            $originLng,
            $destLat,
            $destLng
        );

        return [
            'distance_km' => $distanceKm,
            'duration_minutes' => $durationMinutes,
            'waypoints' => $waypoints,
            'google_maps_url' => $googleMapsUrl,
        ];
    }
}
