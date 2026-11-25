<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uploader_id',
        'uploader_account_type',
        'source_channel',
        'disk',
        'file_path',
        'file_name',
        'stored_filename',
        'extension',
        'mime_type',
        'media_type',
        'file_size_bytes',
        'checksum_sha256',
        'attachable_type',
        'attachable_id',
        'incident_id',
        'conversation_id',
        'message_id',
        'processing_status',
        'processing_error',
        'thumbnail_path',
        'preview_path',
        'width',
        'height',
        'duration_seconds',
        'is_locked_evidence',
        'is_archived',
        'archived_at',
        'archived_by',
        'retention_until',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'decimal:2',
            'is_locked_evidence' => 'boolean',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'retention_until' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class, 'incident_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function geoMetadata(): HasOne
    {
        return $this->hasOne(MediaGeoMetadata::class, 'media_id');
    }

    public function evidenceRecords(): HasMany
    {
        return $this->hasMany(IncidentEvidenceRecord::class, 'media_id');
    }

    public function messages(): BelongsToMany
    {
        return $this->belongsToMany(Message::class, 'message_attachments', 'media_id', 'message_id');
    }

    public function fbAttachments(): HasMany
    {
        return $this->hasMany(FbAttachment::class, 'saved_media_id');
    }

    public function isPhoto(): bool
    {
        return $this->media_type === 'photo' || str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video'
            || str_starts_with((string) $this->mime_type, 'video/')
            || in_array((string) $this->mime_type, ['application/mp4', 'application/quicktime'], true);
    }

    public function isDocument(): bool
    {
        return ! $this->isPhoto() && ! $this->isVideo();
    }

    public function hasAvailableCoordinates(): bool
    {
        $geo = $this->geoMetadata;

        return $geo !== null && $geo->hasCoordinates();
    }

    public function formattedSize(): string
    {
        $bytes = (int) $this->file_size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopePhotosAndVideos(Builder $query): Builder
    {
        return $query->whereIn('media_type', ['photo', 'video']);
    }
}
