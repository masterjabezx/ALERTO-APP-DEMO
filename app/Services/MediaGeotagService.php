<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Incident;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use Carbon\Carbon;

class MediaGeotagService
{
    public function __construct(
        protected GeoService $geoService,
        protected MediaMetadataService $metadataService
    ) {}

    /**
     * Resolve strict GPS coordinates and location source provenance for an attachment.
     *
     * Rules enforced:
     * 1. Never fabricate coordinates or store (0, 0) for unavailable GPS.
     * 2. Distinguish capture coordinates (EXIF/Live Capture) from upload coordinates.
     * 3. Never overwrite verified EXIF capture coordinates with upload or incident-inherited coordinates.
     *
     * @param  array<string, mixed>  $extractedMetadata
     * @param  array<string, mixed>  $geoContext
     */
    public function resolveAndPersistGeoMetadata(
        MediaAttachment $media,
        array $extractedMetadata,
        array $geoContext = [],
        ?Incident $incident = null
    ): MediaGeoMetadata {
        $exifLat = null;
        $exifLng = null;
        if (($extractedMetadata['has_gps'] ?? false) && $this->metadataService->isValidNonZeroCoordinate($extractedMetadata['latitude'] ?? null, $extractedMetadata['longitude'] ?? null)) {
            $exifLat = round((float) $extractedMetadata['latitude'], 8);
            $exifLng = round((float) $extractedMetadata['longitude'], 8);
        }

        $uploadLat = null;
        $uploadLng = null;
        $uploadAcc = isset($geoContext['accuracy']) && is_numeric($geoContext['accuracy']) ? round((float) $geoContext['accuracy'], 2) : null;
        if (isset($geoContext['latitude'], $geoContext['longitude'])
            && is_numeric($geoContext['latitude'])
            && is_numeric($geoContext['longitude'])) {
            $cLat = (float) $geoContext['latitude'];
            $cLng = (float) $geoContext['longitude'];
            if ($this->metadataService->isValidNonZeroCoordinate($cLat, $cLng)) {
                $uploadLat = round($cLat, 8);
                $uploadLng = round($cLng, 8);
            }
        }

        $inheritedLat = null;
        $inheritedLng = null;
        if ($incident && $incident->location) {
            $iLat = (float) $incident->location->latitude;
            $iLng = (float) $incident->location->longitude;
            if ($this->metadataService->isValidNonZeroCoordinate($iLat, $iLng)) {
                $inheritedLat = round($iLat, 8);
                $inheritedLng = round($iLng, 8);
            }
        }

        $requestedSource = $geoContext['location_source'] ?? $geoContext['gps_source'] ?? null;
        $allowInherit = (bool) ($geoContext['allow_incident_inherit'] ?? false);

        // Determine authoritative primary coordinate & location_source
        $primaryLat = null;
        $primaryLng = null;
        $primaryAcc = $extractedMetadata['accuracy_meters'] ?? null;
        $locationSource = MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE;
        $isInherited = false;
        $verificationStatus = 'unavailable';

        if ($requestedSource === MediaGeoMetadata::SOURCE_MESSENGER_SHARED && $uploadLat !== null) {
            $primaryLat = $uploadLat;
            $primaryLng = $uploadLng;
            $primaryAcc = $uploadAcc;
            $locationSource = MediaGeoMetadata::SOURCE_MESSENGER_SHARED;
            $verificationStatus = 'unverified';
        } elseif ($requestedSource === MediaGeoMetadata::SOURCE_ADMIN_CONFIRMED && $uploadLat !== null) {
            $primaryLat = $uploadLat;
            $primaryLng = $uploadLng;
            $primaryAcc = $uploadAcc;
            $locationSource = MediaGeoMetadata::SOURCE_ADMIN_CONFIRMED;
            $verificationStatus = 'verified';
        } elseif ($exifLat !== null && $exifLng !== null) {
            // Original gallery EXIF coordinates always win over upload/inherited locations!
            $primaryLat = $exifLat;
            $primaryLng = $exifLng;
            $locationSource = MediaGeoMetadata::SOURCE_EXIF_CAPTURE;
            $verificationStatus = 'verified';
        } elseif (($requestedSource === MediaGeoMetadata::SOURCE_GPS_CAPTURE || $requestedSource === 'device_live_upload' || ! empty($geoContext['is_live_capture'])) && $uploadLat !== null) {
            $primaryLat = $uploadLat;
            $primaryLng = $uploadLng;
            $primaryAcc = $uploadAcc;
            $locationSource = MediaGeoMetadata::SOURCE_GPS_CAPTURE;
            $verificationStatus = 'unverified';
        } elseif ($uploadLat !== null && $uploadLng !== null) {
            // Explicit upload location when user selected gallery file without EXIF
            $primaryLat = $uploadLat;
            $primaryLng = $uploadLng;
            $primaryAcc = $uploadAcc;
            $locationSource = MediaGeoMetadata::SOURCE_UPLOAD_LOCATION;
            $verificationStatus = 'unverified';
        } elseif ($allowInherit && $inheritedLat !== null && $inheritedLng !== null) {
            $primaryLat = $inheritedLat;
            $primaryLng = $inheritedLng;
            $locationSource = MediaGeoMetadata::SOURCE_INCIDENT_INHERITED;
            $isInherited = true;
            $verificationStatus = 'unverified';
        }

        // Identify Barangay if coordinates are valid
        $barangayId = $geoContext['barangay_id'] ?? null;
        if ($barangayId === null && $primaryLat !== null && $primaryLng !== null) {
            $barangay = $this->geoService->identifyBarangay($primaryLat, $primaryLng);
            $barangayId = $barangay?->id;
        }

        $capturedAt = $extractedMetadata['captured_at'] ?? null;
        if ($capturedAt === null && $locationSource === MediaGeoMetadata::SOURCE_GPS_CAPTURE) {
            $capturedAt = now();
        }
        if ($capturedAt === null && isset($geoContext['captured_at'])) {
            $capturedAt = $geoContext['captured_at'] instanceof Carbon
                ? $geoContext['captured_at']
                : Carbon::parse((string) $geoContext['captured_at']);
        }

        return MediaGeoMetadata::updateOrCreate(
            ['media_id' => $media->id],
            [
                'latitude' => $primaryLat,
                'longitude' => $primaryLng,
                'accuracy_meters' => $primaryAcc,
                'gps_source' => MediaGeoMetadata::mapToLegacyGpsSource($locationSource),
                'location_source' => $locationSource,
                'is_inherited' => $isInherited,
                'exif_latitude' => $exifLat,
                'exif_longitude' => $exifLng,
                'upload_latitude' => $uploadLat,
                'upload_longitude' => $uploadLng,
                'upload_accuracy_meters' => $uploadAcc,
                'inherited_latitude' => $inheritedLat,
                'inherited_longitude' => $inheritedLng,
                'barangay_id' => $barangayId,
                'verification_status' => $verificationStatus,
                'camera_make' => $extractedMetadata['camera_make'] ?? null,
                'camera_model' => $extractedMetadata['camera_model'] ?? null,
                'captured_at' => $capturedAt,
                'uploaded_at' => now(),
                'raw_metadata' => $extractedMetadata['raw_metadata'] ?? [],
            ]
        );
    }

    /**
     * Associate confirmed incident coordinates ONLY when attachment lacks independent verified capture GPS.
     */
    public function applyIncidentInheritedLocation(MediaAttachment $media, Incident $incident): ?MediaGeoMetadata
    {
        if (! $incident->location) {
            return $media->geoMetadata;
        }

        $iLat = (float) $incident->location->latitude;
        $iLng = (float) $incident->location->longitude;

        if (! $this->metadataService->isValidNonZeroCoordinate($iLat, $iLng)) {
            return $media->geoMetadata;
        }

        $geo = $media->geoMetadata;
        if (! $geo) {
            return MediaGeoMetadata::create([
                'media_id' => $media->id,
                'latitude' => round($iLat, 8),
                'longitude' => round($iLng, 8),
                'inherited_latitude' => round($iLat, 8),
                'inherited_longitude' => round($iLng, 8),
                'gps_source' => 'incident_inherited',
                'location_source' => MediaGeoMetadata::SOURCE_INCIDENT_INHERITED,
                'is_inherited' => true,
                'barangay_id' => $incident->barangay_id,
                'verification_status' => 'unverified',
                'uploaded_at' => $media->created_at ?? now(),
            ]);
        }

        // Preserve any verified original capture / EXIF coordinates! Never overwrite them!
        if (in_array($geo->location_source, [
            MediaGeoMetadata::SOURCE_EXIF_CAPTURE,
            MediaGeoMetadata::SOURCE_GPS_CAPTURE,
            MediaGeoMetadata::SOURCE_MESSENGER_SHARED,
            MediaGeoMetadata::SOURCE_ADMIN_CONFIRMED,
        ], true) && $geo->hasCoordinates()) {
            $geo->update([
                'inherited_latitude' => round($iLat, 8),
                'inherited_longitude' => round($iLng, 8),
            ]);

            return $geo->fresh();
        }

        // Only populate primary coords as inherited when original coordinates were unavailable
        $geo->update([
            'latitude' => round($iLat, 8),
            'longitude' => round($iLng, 8),
            'inherited_latitude' => round($iLat, 8),
            'inherited_longitude' => round($iLng, 8),
            'gps_source' => 'incident_inherited',
            'location_source' => MediaGeoMetadata::SOURCE_INCIDENT_INHERITED,
            'is_inherited' => true,
            'barangay_id' => $geo->barangay_id ?? $incident->barangay_id,
            'verification_status' => 'unverified',
        ]);

        return $geo->fresh();
    }

    /**
     * Allow Admin to explicitly confirm/override verified attachment coordinates on map.
     */
    public function adminConfirmLocation(MediaAttachment $media, float $latitude, float $longitude, ?int $barangayId = null): MediaGeoMetadata
    {
        if (! $this->metadataService->isValidNonZeroCoordinate($latitude, $longitude)) {
            throw new \InvalidArgumentException('Confirmed coordinates are invalid or zero.');
        }

        $resolvedBarangayId = $barangayId ?? $this->geoService->identifyBarangay($latitude, $longitude)?->id;

        return MediaGeoMetadata::updateOrCreate(
            ['media_id' => $media->id],
            [
                'latitude' => round($latitude, 8),
                'longitude' => round($longitude, 8),
                'gps_source' => MediaGeoMetadata::mapToLegacyGpsSource(MediaGeoMetadata::SOURCE_ADMIN_CONFIRMED),
                'location_source' => MediaGeoMetadata::SOURCE_ADMIN_CONFIRMED,
                'is_inherited' => false,
                'barangay_id' => $resolvedBarangayId,
                'verification_status' => 'verified',
            ]
        );
    }
}
