<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmergencyHotline;
use App\Models\Office;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \LogicException('Official hotline and Facebook settings cannot be seeded in production.');
        }

        // System Settings
        $settings = [
            [
                'key' => 'dial_daph_command_center_hotline',
                'value' => '0917-123-4567',
                'cast_type' => 'string',
                'description' => 'Official 24/7 Hotline for DIAL DAPH COMMAND CENTER (Hinoba-an LDRRMO).',
            ],
            [
                'key' => 'dial_daph_command_center_title',
                'value' => 'DIAL DAPH COMMAND CENTER',
                'cast_type' => 'string',
                'description' => 'Hotline banner title for DIAL DAPH Command Center button.',
            ],
            [
                'key' => 'dial_daph_command_center_description',
                'value' => '24/7 Municipal Emergency Receiving & Call Verification Desk',
                'cast_type' => 'string',
                'description' => 'Subtitle shown on the DIAL DAPH Command Center call card.',
            ],
            [
                'key' => 'dial_daph_command_center_active',
                'value' => '1',
                'cast_type' => 'boolean',
                'description' => 'Whether the DIAL DAPH Command Center hotline button is active.',
            ],
            [
                'key' => 'ldrrmo_hotline_title',
                'value' => 'LDRRMO',
                'cast_type' => 'string',
                'description' => 'Hotline button title for Hinoba-an LDRRMO direct line.',
            ],
            [
                'key' => 'ldrrmo_hotline_number',
                'value' => '0917-800-9111',
                'cast_type' => 'string',
                'description' => 'Official direct phone number for Hinoba-an LDRRMO.',
            ],
            [
                'key' => 'ldrrmo_hotline_description',
                'value' => 'Hinoba-an Local Disaster Risk Reduction & Management Office',
                'cast_type' => 'string',
                'description' => 'Subtitle shown on the LDRRMO hotline call card.',
            ],
            [
                'key' => 'ldrrmo_hotline_active',
                'value' => '1',
                'cast_type' => 'boolean',
                'description' => 'Whether the LDRRMO hotline button is active.',
            ],
            [
                'key' => 'official_facebook_page_url',
                'value' => 'https://facebook.com/hinobaan.ldrmo',
                'cast_type' => 'string',
                'description' => 'Official Facebook page for Hinoba-an LDRRMO announcements.',
            ],
            [
                'key' => 'max_upload_size_mb',
                'value' => '25',
                'cast_type' => 'integer',
                'description' => 'Maximum upload file size in megabytes for incident photos and videos.',
            ],
            [
                'key' => 'max_upload_count',
                'value' => '5',
                'cast_type' => 'integer',
                'description' => 'Maximum number of media attachments allowed per incident report.',
            ],
            [
                'key' => 'media_max_photo_size_mb',
                'value' => '15',
                'cast_type' => 'integer',
                'description' => 'Maximum upload file size in megabytes for photographs.',
            ],
            [
                'key' => 'media_max_video_size_mb',
                'value' => '50',
                'cast_type' => 'integer',
                'description' => 'Maximum upload file size in megabytes for videos.',
            ],
            [
                'key' => 'media_max_document_size_mb',
                'value' => '20',
                'cast_type' => 'integer',
                'description' => 'Maximum upload file size in megabytes for approved documents.',
            ],
            [
                'key' => 'media_allowed_photo_types',
                'value' => 'jpeg,jpg,png,webp',
                'cast_type' => 'string',
                'description' => 'Comma-separated list of allowed photograph extensions.',
            ],
            [
                'key' => 'media_allowed_video_types',
                'value' => 'mp4,webm,mov',
                'cast_type' => 'string',
                'description' => 'Comma-separated list of allowed video extensions.',
            ],
            [
                'key' => 'media_allowed_document_types',
                'value' => 'pdf,doc,docx',
                'cast_type' => 'string',
                'description' => 'Comma-separated list of allowed document extensions.',
            ],
            [
                'key' => 'media_retention_days',
                'value' => '365',
                'cast_type' => 'integer',
                'description' => 'Default number of days before archived operational evidence qualifies for retention review.',
            ],
            [
                'key' => 'media_auto_generate_thumbnails',
                'value' => '1',
                'cast_type' => 'boolean',
                'description' => 'Automatically generate thumbnails and previews via Laravel Queue.',
            ],
            [
                'key' => 'media_malware_scan_enabled',
                'value' => '0',
                'cast_type' => 'boolean',
                'description' => 'Enable external malware scanner hook on uploaded media.',
            ],
            [
                'key' => 'municipal_boundary_lat_min',
                'value' => '9.4000',
                'cast_type' => 'string',
                'description' => 'Minimum latitude bounding coordinate for Hinoba-an municipality.',
            ],
            [
                'key' => 'municipal_boundary_lat_max',
                'value' => '9.7500',
                'cast_type' => 'string',
                'description' => 'Maximum latitude bounding coordinate for Hinoba-an municipality.',
            ],
            [
                'key' => 'municipal_boundary_lng_min',
                'value' => '122.3500',
                'cast_type' => 'string',
                'description' => 'Minimum longitude bounding coordinate for Hinoba-an municipality.',
            ],
            [
                'key' => 'municipal_boundary_lng_max',
                'value' => '122.6500',
                'cast_type' => 'string',
                'description' => 'Maximum longitude bounding coordinate for Hinoba-an municipality.',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Emergency Hotlines
        $mdrrmo = Office::where('code', 'MDRRMO')->first();
        $bfp = Office::where('code', 'BFP')->first();
        $pnp = Office::where('code', 'PNP')->first();
        $rhu = Office::where('code', 'RHU')->first();

        $hotlines = [
            [
                'service_name' => 'Hinoba-an LDRRMO Rescue (DIAL DAPH)',
                'phone_number' => '0917-123-4567',
                'office_id' => $mdrrmo?->id ?? 1,
                'is_toll_free' => true,
                'sort_order' => 1,
            ],
            [
                'service_name' => 'BFP Hinoba-an Fire Station',
                'phone_number' => '0998-765-4321',
                'office_id' => $bfp?->id ?? 1,
                'is_toll_free' => false,
                'sort_order' => 2,
            ],
            [
                'service_name' => 'PNP Hinoba-an Municipal Police',
                'phone_number' => '0922-333-4444',
                'office_id' => $pnp?->id ?? 1,
                'is_toll_free' => false,
                'sort_order' => 3,
            ],
            [
                'service_name' => 'Hinoba-an Rural Health Unit (RHU)',
                'phone_number' => '0935-555-6666',
                'office_id' => $rhu?->id ?? 1,
                'is_toll_free' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($hotlines as $hl) {
            EmergencyHotline::updateOrCreate(
                ['service_name' => $hl['service_name']],
                $hl
            );
        }
    }
}
