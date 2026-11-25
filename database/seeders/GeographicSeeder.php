<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\BarangayBoundary;
use App\Models\Municipality;
use Illuminate\Database\Seeder;

class GeographicSeeder extends Seeder
{
    public function run(): void
    {
        // Hinoba-an, Negros Occidental — Official Municipality for ALERTO APP LDRRMO
        $municipality = Municipality::firstOrCreate(
            ['name' => 'Hinoba-an'],
            [
                'province' => 'Negros Occidental',
                'region' => 'Region VI (Western Visayas)',
                'postal_code' => '6114',
                'center_latitude' => 9.5936,
                'center_longitude' => 122.4542,
            ]
        );

        // 13 Official Barangays of Hinoba-an
        $hinobaanBarangays = [
            ['name' => 'Alim', 'lat' => 9.5620, 'lng' => 122.4680],
            ['name' => 'Asia', 'lat' => 9.5410, 'lng' => 122.5020],
            ['name' => 'Bacuyangan', 'lat' => 9.6150, 'lng' => 122.4350],
            ['name' => 'Barangay I (Poblacion)', 'lat' => 9.5936, 'lng' => 122.4542],
            ['name' => 'Barangay II (Poblacion)', 'lat' => 9.5980, 'lng' => 122.4580],
            ['name' => 'Bulwangan', 'lat' => 9.5820, 'lng' => 122.4790],
            ['name' => 'Culipapa', 'lat' => 9.5100, 'lng' => 122.5200],
            ['name' => 'Damutan', 'lat' => 9.6800, 'lng' => 122.5600],
            ['name' => 'Daug', 'lat' => 9.6350, 'lng' => 122.4410],
            ['name' => 'Po-ok', 'lat' => 9.5750, 'lng' => 122.4610],
            ['name' => 'San Rafael', 'lat' => 9.6500, 'lng' => 122.4800],
            ['name' => 'Sangke', 'lat' => 9.4850, 'lng' => 122.5350],
            ['name' => 'Talacagay', 'lat' => 9.6620, 'lng' => 122.4280],
        ];

        foreach ($hinobaanBarangays as $bData) {
            $barangay = Barangay::firstOrCreate(
                [
                    'municipality_id' => $municipality->id,
                    'name' => $bData['name'],
                ],
                [
                    'contact_number' => null,
                    'center_latitude' => $bData['lat'],
                    'center_longitude' => $bData['lng'],
                ]
            );

            // Bounding box (approx 0.02 deg ~ 2.2km radius)
            $minLat = $bData['lat'] - 0.020;
            $maxLat = $bData['lat'] + 0.020;
            $minLng = $bData['lng'] - 0.020;
            $maxLng = $bData['lng'] + 0.020;

            $polygonGeoJson = json_encode([
                'type' => 'Polygon',
                'coordinates' => [[
                    [$minLng, $minLat],
                    [$maxLng, $minLat],
                    [$maxLng, $maxLat],
                    [$minLng, $maxLat],
                    [$minLng, $minLat],
                ]],
            ]);

            BarangayBoundary::firstOrCreate(
                ['barangay_id' => $barangay->id],
                [
                    'geojson_boundary' => $polygonGeoJson,
                    'bounding_min_lat' => $minLat,
                    'bounding_max_lat' => $maxLat,
                    'bounding_min_lng' => $minLng,
                    'bounding_max_lng' => $maxLng,
                ]
            );
        }
    }
}
