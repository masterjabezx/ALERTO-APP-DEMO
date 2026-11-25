<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\EmergencyHotline;
use App\Models\Office;
use App\Models\OfficeLocation;
use App\Models\OfficeResponsibility;
use App\Models\ResponseTeam;
use Illuminate\Database\Seeder;

class OfficeAndTeamSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Operational offices and emergency contacts cannot be seeded in production.');
        }

        $primaryBarangay = Barangay::first();
        $barangayId = $primaryBarangay ? $primaryBarangay->id : 1;

        $offices = [
            [
                'name' => 'General Santos City Police Station (PNP)',
                'code' => 'PNP_GENSAN',
                'department_type' => 'police',
                'contact_phone' => '(083) 552-3111',
                'responsibilities' => ['crime-security'],
                'hotline' => '117 / 911',
                'teams' => [
                    ['team_name' => 'Mobile Patrol Alpha', 'call_sign' => 'PATROL-01', 'team_type' => 'police'],
                    ['team_name' => 'Tactical Response Unit', 'call_sign' => 'TACTICAL-01', 'team_type' => 'police'],
                ],
            ],
            [
                'name' => 'Bureau of Fire Protection - Central (BFP)',
                'code' => 'BFP_GENSAN',
                'department_type' => 'fire',
                'contact_phone' => '(083) 552-3222',
                'responsibilities' => ['fire-emergency'],
                'hotline' => '(083) 552-3222',
                'teams' => [
                    ['team_name' => 'Engine Company 1', 'call_sign' => 'ENGINE-01', 'team_type' => 'fire'],
                    ['team_name' => 'Ladder Company 1', 'call_sign' => 'LADDER-01', 'team_type' => 'fire'],
                ],
            ],
            [
                'name' => 'City Disaster Risk Reduction & Management Office (CDRRMO)',
                'code' => 'CDRRMO_GENSAN',
                'department_type' => 'disaster',
                'contact_phone' => '(083) 552-3333',
                'responsibilities' => ['disaster-rescue', 'traffic-hazard'],
                'hotline' => '911 / (083) 552-3333',
                'teams' => [
                    ['team_name' => 'Rescue Squad Alpha', 'call_sign' => 'RESCUE-01', 'team_type' => 'disaster'],
                    ['team_name' => 'Water Search & Rescue', 'call_sign' => 'WASAR-01', 'team_type' => 'disaster'],
                ],
            ],
            [
                'name' => 'City Health Office - Emergency Medical Services (EMS)',
                'code' => 'CHO_EMS_GENSAN',
                'department_type' => 'medical',
                'contact_phone' => '(083) 552-3444',
                'responsibilities' => ['medical-emergency'],
                'hotline' => '(083) 552-3444',
                'teams' => [
                    ['team_name' => 'Advanced Life Support Unit', 'call_sign' => 'MEDIC-01', 'team_type' => 'medical'],
                    ['team_name' => 'Basic Life Support Unit', 'call_sign' => 'MEDIC-02', 'team_type' => 'medical'],
                ],
            ],
        ];

        foreach ($offices as $data) {
            $office = Office::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'department_type' => $data['department_type'],
                    'contact_phone' => $data['contact_phone'],
                    'is_active' => true,
                ]
            );

            OfficeLocation::firstOrCreate(
                ['office_id' => $office->id],
                [
                    'barangay_id' => $barangayId,
                    'street_address' => 'City Hall Complex, Roxas East Avenue',
                    'latitude' => 6.1164,
                    'longitude' => 125.1716,
                    'service_radius_meters' => 15000,
                ]
            );

            foreach ($data['responsibilities'] as $slug) {
                OfficeResponsibility::firstOrCreate([
                    'office_id' => $office->id,
                    'category_slug' => $slug,
                ]);
            }

            EmergencyHotline::firstOrCreate(
                ['service_name' => $data['name']],
                [
                    'phone_number' => $data['hotline'],
                    'office_id' => $office->id,
                    'is_toll_free' => false,
                    'sort_order' => 10,
                ]
            );

            foreach ($data['teams'] as $teamData) {
                ResponseTeam::firstOrCreate(
                    ['call_sign' => $teamData['call_sign']],
                    [
                        'office_id' => $office->id,
                        'team_name' => $teamData['team_name'],
                        'team_type' => $teamData['team_type'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
