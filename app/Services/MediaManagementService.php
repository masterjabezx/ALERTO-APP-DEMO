<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ProcessMediaAttachmentJob;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\IncidentEvidenceRecord;
use App\Models\MediaAttachment;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\MediaPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaManagementService
{
    public function __construct(
        protected MediaMetadataService $metadataService,
        protected MediaGeotagService $geotagService
    ) {}

    /**
     * Centralized ingestion for all uploaded files (Citizen, Personnel, Office, Admin).
     *
     * @param  array<string, mixed>  $options
     */
    public function storeUploadedFile(UploadedFile $file, ?User $uploader, array $options = []): MediaAttachment
    {
        $allowedCategories = $options['allowed_categories'] ?? ['photo', 'video', 'document'];
        $validated = $this->metadataService->validateUploadedFile($file, $allowedCategories);

        $disk = $options['disk'] ?? 'private';
        $sourceChannel = $options['source_channel'] ?? ($uploader?->account_type ? "{$uploader->account_type}_upload" : 'citizen_portal');
        $extension = $validated['extension'];
        $randomizedFilename = Str::uuid()->toString().'.'.$extension;
        $directory = sprintf('media/%s/%s/%s', date('Y'), date('m'), preg_replace('/[^a-z0-9_-]/i', '_', $sourceChannel));

        $realPath = $file->getRealPath();
        $checksum = $realPath ? (hash_file('sha256', $realPath) ?: '') : '';

        // Save untouched original file on private storage disk with randomized filename
        $storedPath = $file->storeAs($directory, $randomizedFilename, $disk);

        $absolutePath = Storage::disk($disk)->path($storedPath);
        $extractedMetadata = $this->metadataService->extractMetadata($absolutePath, $validated['mime_type']);

        $incident = null;
        if (! empty($options['incident_id'])) {
            $incident = Incident::with('location')->find($options['incident_id']);
        }

        /** @var ?Model $attachable */
        $attachable = $options['attachable'] ?? null;

        return DB::transaction(function () use (
            $uploader,
            $sourceChannel,
            $disk,
            $storedPath,
            $file,
            $randomizedFilename,
            $validated,
            $checksum,
            $extractedMetadata,
            $incident,
            $attachable,
            $options
        ): MediaAttachment {
            $config = $this->metadataService->getUploadPolicyConfiguration();

            $media = MediaAttachment::create([
                'uploader_id' => $uploader?->id,
                'uploader_account_type' => $uploader?->account_type ?? ($options['uploader_account_type'] ?? 'external'),
                'source_channel' => $sourceChannel,
                'disk' => $disk,
                'file_path' => $storedPath,
                'file_name' => Str::limit($file->getClientOriginalName(), 240, ''),
                'stored_filename' => $randomizedFilename,
                'extension' => $validated['extension'],
                'mime_type' => $validated['mime_type'],
                'media_type' => $validated['media_type'],
                'file_size_bytes' => $validated['file_size_bytes'],
                'checksum_sha256' => $checksum,
                'attachable_type' => $attachable ? $attachable::class : ($options['attachable_type'] ?? null),
                'attachable_id' => $attachable?->getKey() ?? ($options['attachable_id'] ?? null),
                'incident_id' => $incident?->id,
                'conversation_id' => $options['conversation_id'] ?? null,
                'message_id' => $options['message_id'] ?? null,
                'processing_status' => 'uploaded',
                'width' => $extractedMetadata['width'],
                'height' => $extractedMetadata['height'],
                'duration_seconds' => $extractedMetadata['duration_seconds'],
                'is_locked_evidence' => (bool) ($options['is_locked_evidence'] ?? ($incident?->hasLockedEvidence() ?? false)),
                'retention_until' => now()->addDays($config['retention_days']),
                'created_at' => now(),
            ]);

            // Resolve and persist strict geotagging metadata
            $this->geotagService->resolveAndPersistGeoMetadata(
                $media,
                $extractedMetadata,
                $options['geo_context'] ?? [],
                $incident
            );

            // Optional direct link to IncidentEvidenceRecord
            if ($incident && ! empty($options['link_as_evidence'])) {
                IncidentEvidenceRecord::create([
                    'incident_id' => $incident->id,
                    'media_id' => $media->id,
                    'evidence_type' => $options['evidence_type'] ?? 'citizen_submission',
                    'caption' => $options['caption'] ?? 'Submitted via ALERTO centralized media pipeline.',
                    'created_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $uploader?->id,
                'event_type' => 'media:uploaded',
                'auditable_type' => MediaAttachment::class,
                'auditable_id' => $media->id,
                'new_values' => [
                    'source_channel' => $sourceChannel,
                    'media_type' => $validated['media_type'],
                    'checksum_sha256' => $checksum,
                    'incident_id' => $incident?->id,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            if (empty($options['skip_processing'])) {
                ProcessMediaAttachmentJob::dispatch($media->id);
            }

            return $media->load('geoMetadata');
        });
    }

    /**
     * Centralized ingestion for binary payloads downloaded from remote platforms (e.g., Meta Messenger CDN).
     *
     * @param  array<string, mixed>  $options
     */
    public function storeRemoteBinaryContent(string $binary, string $originalFilename, string $mimeType, array $options = []): MediaAttachment
    {
        $mediaType = str_starts_with($mimeType, 'video/') ? 'video' : (str_starts_with($mimeType, 'image/') ? 'photo' : 'document');
        $this->metadataService->validateBinaryPayload($binary, $mediaType);

        $disk = $options['disk'] ?? 'private';
        $sourceChannel = $options['source_channel'] ?? 'facebook_messenger';
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION)) ?: ($mediaType === 'video' ? 'mp4' : 'jpg');
        $randomizedFilename = Str::uuid()->toString().'.'.$extension;
        $path = sprintf('media/%s/%s/%s/%s', date('Y'), date('m'), $sourceChannel, $randomizedFilename);

        Storage::disk($disk)->put($path, $binary);
        $checksum = hash('sha256', $binary);

        $absolutePath = Storage::disk($disk)->path($path);
        $extractedMetadata = $this->metadataService->extractMetadata($absolutePath, $mimeType);

        $config = $this->metadataService->getUploadPolicyConfiguration();

        $media = MediaAttachment::create([
            'uploader_id' => $options['uploader_id'] ?? null,
            'uploader_account_type' => $options['uploader_account_type'] ?? 'facebook_citizen',
            'source_channel' => $sourceChannel,
            'disk' => $disk,
            'file_path' => $path,
            'file_name' => Str::limit($originalFilename, 240, ''),
            'stored_filename' => $randomizedFilename,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'media_type' => $mediaType,
            'file_size_bytes' => strlen($binary),
            'checksum_sha256' => $checksum,
            'attachable_type' => $options['attachable_type'] ?? null,
            'attachable_id' => $options['attachable_id'] ?? null,
            'incident_id' => $options['incident_id'] ?? null,
            'processing_status' => 'uploaded',
            'width' => $extractedMetadata['width'],
            'height' => $extractedMetadata['height'],
            'duration_seconds' => $extractedMetadata['duration_seconds'],
            'retention_until' => now()->addDays($config['retention_days']),
            'created_at' => now(),
        ]);

        $this->geotagService->resolveAndPersistGeoMetadata(
            $media,
            $extractedMetadata,
            $options['geo_context'] ?? []
        );

        if (empty($options['skip_processing'])) {
            ProcessMediaAttachmentJob::dispatch($media->id);
        }

        return $media->load('geoMetadata');
    }

    /**
     * Link an existing MediaAttachment (e.g. from Messenger or Intake) as formal Incident Evidence.
     */
    public function linkMediaToIncident(
        MediaAttachment $media,
        Incident $incident,
        string $evidenceType = 'citizen_submission',
        ?string $caption = null,
        bool $inheritIncidentLocation = true
    ): IncidentEvidenceRecord {
        if ($media->incident_id === null) {
            $media->update(['incident_id' => $incident->id]);
        }

        $record = IncidentEvidenceRecord::firstOrCreate(
            [
                'incident_id' => $incident->id,
                'media_id' => $media->id,
            ],
            [
                'evidence_type' => $evidenceType,
                'caption' => $caption ?? 'Linked operational evidence.',
                'created_at' => now(),
            ]
        );

        if ($inheritIncidentLocation) {
            $this->geotagService->applyIncidentInheritedLocation($media, $incident);
        }

        return $record;
    }

    /**
     * Lock all evidence linked to an incident once accepted to protect chain-of-custody.
     */
    public function lockIncidentEvidence(Incident $incident): void
    {
        $mediaIds = $incident->evidenceRecords()->pluck('media_id')->merge(
            $incident->mediaAttachments()->pluck('id')
        )->unique()->values();

        if ($mediaIds->isNotEmpty()) {
            MediaAttachment::whereIn('id', $mediaIds)->update(['is_locked_evidence' => true]);
        }
    }

    /**
     * Check whether a user is authorized to view or stream a media attachment.
     */
    public function canAccessMedia(User $user, MediaAttachment $media): bool
    {
        return app(MediaPolicy::class)->view($user, $media);
    }

    /**
     * Verify SHA-256 checksum integrity of stored file against original database hash.
     */
    public function verifyFileIntegrity(MediaAttachment $media): bool
    {
        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->file_path)) {
            return false;
        }

        $actualHash = hash_file('sha256', $disk->path($media->file_path));

        return $actualHash !== false && hash_equals((string) $media->checksum_sha256, $actualHash);
    }

    /**
     * Audited archiving of evidence (never permanently deletes operational files).
     */
    public function archiveMedia(MediaAttachment $media, User $admin, string $reason): MediaAttachment
    {
        $media->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $admin->id,
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'event_type' => 'media:archived',
            'auditable_type' => MediaAttachment::class,
            'auditable_id' => $media->id,
            'new_values' => ['reason' => $reason, 'archived_at' => now()->toIso8601String()],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return $media->fresh();
    }

    /**
     * Audited retention purge (only allowed when archived & not locked to an open active incident).
     *
     * @throws InvalidArgumentException
     */
    public function auditedRetentionPurge(MediaAttachment $media, User $admin, string $justification): void
    {
        if (! $media->is_archived) {
            throw new InvalidArgumentException('Operational evidence must be formally archived before retention disposal.');
        }

        if ($media->incident && ! in_array($media->incident->status, ['resolved', 'closed', 'rejected'], true)) {
            throw new InvalidArgumentException('Cannot purge evidence attached to an active or open incident.');
        }

        DB::transaction(function () use ($media, $admin, $justification): void {
            AuditLog::create([
                'user_id' => $admin->id,
                'event_type' => 'media:retention_purged',
                'auditable_type' => MediaAttachment::class,
                'auditable_id' => $media->id,
                'old_values' => [
                    'file_name' => $media->file_name,
                    'checksum_sha256' => $media->checksum_sha256,
                    'incident_id' => $media->incident_id,
                ],
                'new_values' => ['justification' => $justification],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            $media->delete(); // Soft delete for audit integrity; binary file retention handled safely
        });
    }

    /**
     * Update Admin media upload & retention system settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(array $data, User $admin): void
    {
        DB::transaction(function () use ($data, $admin): void {
            SystemSetting::set('media_max_photo_size_mb', (int) $data['max_photo_size_mb'], 'integer');
            SystemSetting::set('media_max_video_size_mb', (int) $data['max_video_size_mb'], 'integer');
            SystemSetting::set('media_max_document_size_mb', (int) $data['max_document_size_mb'], 'integer');
            SystemSetting::set('max_upload_count', (int) $data['max_upload_count'], 'integer');
            SystemSetting::set('media_allowed_photo_types', strtolower((string) $data['allowed_photo_types']), 'string');
            SystemSetting::set('media_allowed_video_types', strtolower((string) $data['allowed_video_types']), 'string');
            SystemSetting::set('media_allowed_document_types', strtolower((string) $data['allowed_document_types']), 'string');
            SystemSetting::set('media_retention_days', (int) $data['retention_days'], 'integer');
            SystemSetting::set('media_auto_generate_thumbnails', ! empty($data['auto_generate_thumbnails']), 'boolean');
            SystemSetting::set('media_malware_scan_enabled', ! empty($data['malware_scan_enabled']), 'boolean');

            AuditLog::create([
                'user_id' => $admin->id,
                'event_type' => 'media:settings_updated',
                'auditable_type' => SystemSetting::class,
                'auditable_id' => 1,
                'new_values' => $data,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Query paginated media attachments with comprehensive Admin filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredMedia(array $filters = [], int $perPage = 24, bool $evidenceOnly = false): LengthAwarePaginator
    {
        $query = MediaAttachment::with([
            'uploader',
            'geoMetadata.barangay',
            'incident.category',
            'incident.barangay',
            'evidenceRecords.incident.category',
            'evidenceRecords.incident.barangay',
        ]);

        if ($evidenceOnly) {
            $query->whereIn('media_type', ['photo', 'video'])
                ->where(function (Builder $q): void {
                    $q->whereNotNull('incident_id')
                        ->orWhereHas('evidenceRecords');
                });
        }

        if (! empty($filters['media_type']) && $filters['media_type'] !== 'all') {
            $query->where('media_type', $filters['media_type']);
        }

        if (! empty($filters['source_channel']) && $filters['source_channel'] !== 'all') {
            $query->where('source_channel', $filters['source_channel']);
        }

        if (! empty($filters['processing_status']) && $filters['processing_status'] !== 'all') {
            $query->where('processing_status', $filters['processing_status']);
        }

        if (! empty($filters['location_source']) && $filters['location_source'] !== 'all') {
            $locSource = $filters['location_source'];
            $query->whereHas('geoMetadata', fn (Builder $g) => $g->where('location_source', $locSource));
        }

        if (! empty($filters['uploader_id'])) {
            $query->where('uploader_id', (int) $filters['uploader_id']);
        }

        if (! empty($filters['incident_reference'])) {
            $ref = trim((string) $filters['incident_reference']);
            $query->where(function (Builder $q) use ($ref): void {
                $q->whereHas('incident', fn (Builder $inc) => $inc->where('reference_number', 'like', "%{$ref}%"))
                    ->orWhereHas('evidenceRecords.incident', fn (Builder $inc) => $inc->where('reference_number', 'like', "%{$ref}%"));
            });
        }

        if (! empty($filters['category_id'])) {
            $catId = (int) $filters['category_id'];
            $query->where(function (Builder $q) use ($catId): void {
                $q->whereHas('incident', fn (Builder $inc) => $inc->where('category_id', $catId))
                    ->orWhereHas('evidenceRecords.incident', fn (Builder $inc) => $inc->where('category_id', $catId));
            });
        }

        if (! empty($filters['barangay_id'])) {
            $brgyId = (int) $filters['barangay_id'];
            $query->where(function (Builder $q) use ($brgyId): void {
                $q->whereHas('geoMetadata', fn (Builder $g) => $g->where('barangay_id', $brgyId))
                    ->orWhereHas('incident', fn (Builder $inc) => $inc->where('barangay_id', $brgyId))
                    ->orWhereHas('evidenceRecords.incident', fn (Builder $inc) => $inc->where('barangay_id', $brgyId));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['archived']) && $filters['archived'] !== 'all') {
            $query->where('is_archived', $filters['archived'] === 'archived' || $filters['archived'] === '1');
        } else {
            $query->where('is_archived', false);
        }

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($term): void {
                $q->where('file_name', 'like', "%{$term}%")
                    ->orWhere('checksum_sha256', 'like', "%{$term}%")
                    ->orWhereHas('incident', fn (Builder $inc) => $inc->where('reference_number', 'like', "%{$term}%"))
                    ->orWhereHas('evidenceRecords.incident', fn (Builder $inc) => $inc->where('reference_number', 'like', "%{$term}%"))
                    ->orWhereHas('uploader', fn (Builder $u) => $u->where('name', 'like', "%{$term}%"));
            });
        }

        return $query->latest('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Real-time database storage & processing telemetry for Admin Media Management Console.
     *
     * @return array<string, mixed>
     */
    public function getDashboardAnalytics(): array
    {
        $totalPhotos = MediaAttachment::where('media_type', 'photo')->count();
        $totalVideos = MediaAttachment::where('media_type', 'video')->count();
        $totalDocuments = MediaAttachment::where('media_type', 'document')->count();
        $pendingProcessing = MediaAttachment::whereIn('processing_status', ['uploading', 'uploaded', 'processing'])->count();
        $failedProcessing = MediaAttachment::where('processing_status', 'failed')->count();
        $withGeotag = MediaAttachment::whereHas('geoMetadata', function (Builder $q): void {
            $q->whereNotNull('latitude')->whereNotNull('longitude');
        })->count();
        $missingLocation = MediaAttachment::whereDoesntHave('geoMetadata', function (Builder $q): void {
            $q->whereNotNull('latitude')->whereNotNull('longitude');
        })->count();
        $storageBytes = (int) MediaAttachment::sum('file_size_bytes');

        return [
            'total_media' => $totalPhotos + $totalVideos + $totalDocuments,
            'total_photos' => $totalPhotos,
            'total_videos' => $totalVideos,
            'total_documents' => $totalDocuments,
            'pending_processing' => $pendingProcessing,
            'failed_processing' => $failedProcessing,
            'with_geotag' => $withGeotag,
            'missing_location' => $missingLocation,
            'storage_bytes' => $storageBytes,
            'storage_human' => $this->formatBytes($storageBytes),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
