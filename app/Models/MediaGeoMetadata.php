<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaGeoMetadata extends Model
{
    use HasFactory;

    public const SOURCE_GPS_CAPTURE = 'GPS_CAPTURE';

    public const SOURCE_EXIF_CAPTURE = 'EXIF_CAPTURE';

    public const SOURCE_UPLOAD_LOCATION = 'UPLOAD_LOCATION';

    public const SOURCE_INCIDENT_INHERITED = 'INCIDENT_INHERITED_LOCATION';

    public const SOURCE_MESSENGER_SHARED = 'MESSENGER_SHARED_LOCATION';

    public const SOURCE_ADMIN_CONFIRMED = 'ADMIN_CONFIRMED_LOCATION';

    public const SOURCE_LOCATION_UNAVAILABLE = 'LOCATION_UNAVAILABLE';

    public const LOCATION_SOURCES = [
        self::SOURCE_GPS_CAPTURE,
        self::SOURCE_EXIF_CAPTURE,
        self::SOURCE_UPLOAD_LOCATION,
        self::SOURCE_INCIDENT_INHERITED,
        self::SOURCE_MESSENGER_SHARED,
        self::SOURCE_ADMIN_CONFIRMED,
        self::SOURCE_LOCATION_UNAVAILABLE,
    ];

    public $timestamps = false;

    protected $table = 'media_geo_metadata';

    protected $fillable = [
        'media_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'gps_source',
        'location_source',
        'is_inherited',
        'exif_latitude',
        'exif_longitude',
        'upload_latitude',
        'upload_longitude',
        'upload_accuracy_meters',
        'inherited_latitude',
        'inherited_longitude',
        'barangay_id',
        'verification_status',
        'camera_make',
        'camera_model',
        'captured_at',
        'uploaded_at',
        'raw_metadata',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'accuracy_meters' => 'decimal:2',
            'exif_latitude' => 'decimal:8',
            'exif_longitude' => 'decimal:8',
            'upload_latitude' => 'decimal:8',
            'upload_longitude' => 'decimal:8',
            'upload_accuracy_meters' => 'decimal:2',
            'inherited_latitude' => 'decimal:8',
            'inherited_longitude' => 'decimal:8',
            'is_inherited' => 'boolean',
            'captured_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'raw_metadata' => 'array',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAttachment::class, 'media_id');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'barangay_id');
    }

    public function hasCoordinates(): bool
    {
        if ($this->latitude === null || $this->longitude === null) {
            return false;
        }

        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;

        return ! (abs($lat) < 0.00001 && abs($lng) < 0.00001);
    }

    public static function mapToLegacyGpsSource(string $locationSource): string
    {
        return match ($locationSource) {
            self::SOURCE_EXIF_CAPTURE => 'exif_embedded',
            self::SOURCE_INCIDENT_INHERITED => 'incident_inherited',
            self::SOURCE_GPS_CAPTURE,
            self::SOURCE_UPLOAD_LOCATION,
            self::SOURCE_MESSENGER_SHARED,
            self::SOURCE_ADMIN_CONFIRMED => 'device_live_upload',
            default => 'none',
        };
    }
}
