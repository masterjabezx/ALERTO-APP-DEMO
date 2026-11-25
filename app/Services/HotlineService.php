<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HotlineService
{
    /**
     * Retrieve both configurable emergency hotline configurations.
     *
     * @return array{
     *     dial_daph: array{
     *         key: string,
     *         title: string,
     *         number: ?string,
     *         tel_href: ?string,
     *         description: string,
     *         is_active: bool,
     *         is_configured: bool,
     *     },
     *     ldrrmo: array{
     *         key: string,
     *         title: string,
     *         number: ?string,
     *         tel_href: ?string,
     *         description: string,
     *         is_active: bool,
     *         is_configured: bool,
     *     }
     * }
     */
    public function getDualHotlines(): array
    {
        return [
            'dial_daph' => $this->buildHotlinePayload(
                key: 'dial_daph',
                title: (string) SystemSetting::get('dial_daph_command_center_title', 'DIAL DAPH COMMAND CENTER'),
                number: $this->nullableTrimmedString(SystemSetting::get('dial_daph_command_center_hotline')),
                description: (string) SystemSetting::get('dial_daph_command_center_description', '24/7 Municipal Emergency Receiving & Call Verification Desk'),
                isActive: (bool) SystemSetting::get('dial_daph_command_center_active', true),
            ),
            'ldrrmo' => $this->buildHotlinePayload(
                key: 'ldrrmo',
                title: (string) SystemSetting::get('ldrrmo_hotline_title', 'LDRRMO'),
                number: $this->nullableTrimmedString(SystemSetting::get('ldrrmo_hotline_number')),
                description: (string) SystemSetting::get('ldrrmo_hotline_description', 'Hinoba-an Local Disaster Risk Reduction & Management Office'),
                isActive: (bool) SystemSetting::get('ldrrmo_hotline_active', true),
            ),
        ];
    }

    /**
     * Update both configurable emergency hotlines cleanly with audit trail.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDualHotlines(User $actor, array $data): array
    {
        $before = $this->getDualHotlines();

        DB::transaction(function () use ($actor, $data, $before): void {
            SystemSetting::set(
                'dial_daph_command_center_title',
                trim((string) ($data['dial_daph_command_center_title'] ?? 'DIAL DAPH COMMAND CENTER')),
                'string',
                'Hotline button title for DIAL DAPH Command Center button.'
            );
            SystemSetting::set(
                'dial_daph_command_center_hotline',
                trim((string) ($data['dial_daph_command_center_hotline'] ?? '')),
                'string',
                'Official 24/7 Hotline for DIAL DAPH COMMAND CENTER (Hinoba-an LDRRMO).'
            );
            SystemSetting::set(
                'dial_daph_command_center_description',
                trim((string) ($data['dial_daph_command_center_description'] ?? '')),
                'string',
                'Subtitle shown on the DIAL DAPH Command Center call card.'
            );
            SystemSetting::set(
                'dial_daph_command_center_active',
                ! empty($data['dial_daph_command_center_active']),
                'boolean',
                'Whether the DIAL DAPH Command Center hotline button is active.'
            );

            SystemSetting::set(
                'ldrrmo_hotline_title',
                trim((string) ($data['ldrrmo_hotline_title'] ?? 'LDRRMO')),
                'string',
                'Hotline button title for Hinoba-an LDRRMO direct line.'
            );
            SystemSetting::set(
                'ldrrmo_hotline_number',
                trim((string) ($data['ldrrmo_hotline_number'] ?? '')),
                'string',
                'Official direct phone number for Hinoba-an LDRRMO.'
            );
            SystemSetting::set(
                'ldrrmo_hotline_description',
                trim((string) ($data['ldrrmo_hotline_description'] ?? '')),
                'string',
                'Subtitle shown on the LDRRMO hotline call card.'
            );
            SystemSetting::set(
                'ldrrmo_hotline_active',
                ! empty($data['ldrrmo_hotline_active']),
                'boolean',
                'Whether the LDRRMO hotline button is active.'
            );

            AuditLog::create([
                'user_id' => $actor->id,
                'event_type' => 'hotlines:updated',
                'auditable_type' => SystemSetting::class,
                'auditable_id' => 1,
                'old_values' => $before,
                'new_values' => $this->getDualHotlines(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        });

        return $this->getDualHotlines();
    }

    /**
     * Format a phone number safely into an RFC-3966 tel: URI target.
     */
    public function sanitizeTelUri(?string $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        $trimmed = trim($phoneNumber);
        if ($trimmed === '') {
            return null;
        }

        $sanitized = preg_replace('/[^0-9+*#]/', '', $trimmed) ?? '';
        if ($sanitized === '' || strlen(preg_replace('/\D/', '', $sanitized) ?? '') < 3) {
            return null;
        }

        return 'tel:'.$sanitized;
    }

    /**
     * @return array{
     *     key: string,
     *     title: string,
     *     number: ?string,
     *     tel_href: ?string,
     *     description: string,
     *     is_active: bool,
     *     is_configured: bool,
     * }
     */
    protected function buildHotlinePayload(
        string $key,
        string $title,
        ?string $number,
        string $description,
        bool $isActive
    ): array {
        $telHref = $this->sanitizeTelUri($number);
        $isConfigured = $isActive && $telHref !== null;

        return [
            'key' => $key,
            'title' => $title !== '' ? $title : strtoupper(str_replace('_', ' ', $key)),
            'number' => $number,
            'tel_href' => $isConfigured ? $telHref : null,
            'description' => $description,
            'is_active' => $isActive,
            'is_configured' => $isConfigured,
        ];
    }

    protected function nullableTrimmedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
