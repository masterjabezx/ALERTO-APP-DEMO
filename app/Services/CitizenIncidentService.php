<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\EmergencyReportSubmitted;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\IncidentLocation;
use App\Models\IncidentStatusHistory;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CitizenIncidentService
{
    /**
     * Simplified citizen and guest emergency report submission.
     * Collects only reporter identity, phone number, GPS/pinned location, and optional media.
     * Never guesses category, severity, narrative description, or unverified barangays.
     *
     * @param  array<string, mixed>  $data
     * @param  array<UploadedFile>  $attachments
     * @return array{incident: Incident, claim_token: ?string}
     */
    public function submitSimplifiedIncident(?User $reporter, array $data, array $attachments = []): array
    {
        if (! empty($data['idempotency_key'])) {
            $existing = Incident::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return [
                    'incident' => $existing->load(['barangay', 'location', 'evidenceRecords.media']),
                    'claim_token' => null,
                ];
            }
        }

        $result = DB::transaction(function () use ($reporter, $data, $attachments): array {
            $isGuest = $reporter === null;
            $referenceNumber = $this->generateReferenceNumber();
            $plainClaimToken = $isGuest ? Str::random(64) : null;
            $claimTokenHash = $plainClaimToken !== null ? hash('sha256', $plainClaimToken) : null;

            $geoService = app(GeoService::class);
            $verifiedBarangay = $geoService->identifyVerifiedBarangay(
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            $callerName = trim((string) ($data['caller_name'] ?? $reporter?->name ?? 'Emergency Caller'));
            $callerPhone = trim((string) ($data['caller_phone'] ?? $reporter?->phone_number ?? ''));

            $incident = Incident::create([
                'reference_number' => $referenceNumber,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'client_uuid' => $data['client_uuid'] ?? null,
                'reporter_id' => $reporter?->id,
                'is_guest_report' => $isGuest,
                'guest_claim_token_hash' => $claimTokenHash,
                'source_channel' => $isGuest ? 'guest_portal' : 'citizen_portal',
                'category_id' => null,
                'priority' => null,
                'status' => 'submitted',
                'workflow_stage' => 'new_report',
                'assessment_status' => 'unassessed',
                'description' => null,
                'caller_name' => $callerName,
                'caller_phone' => $callerPhone,
                'is_mobile_number_verified' => false,
                'contact_verification_status' => 'unverified',
                'barangay_id' => $verifiedBarangay?->id,
                'submitted_at' => now(),
                'version_lock' => 1,
            ]);

            $locationSource = match ($data['location_source'] ?? 'gps_device') {
                'gps_browser', 'gps_device' => 'gps_device',
                'manual_pin', 'map_picker', 'barangay_centroid' => 'map_picker',
                'fb_shared_location' => 'fb_shared_location',
                'admin_entered' => 'admin_entered',
                default => 'gps_device',
            };

            IncidentLocation::create([
                'incident_id' => $incident->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'street_address' => $data['street_address'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'location_source' => $locationSource,
            ]);

            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'previous_status' => 'submitted',
                'new_status' => 'submitted',
                'changed_by' => $reporter?->id,
                'change_reason' => $isGuest
                    ? 'Guest emergency report received. Awaiting DIAL DAPH Command Center call verification.'
                    : 'Citizen emergency report received. Awaiting DIAL DAPH Command Center call verification.',
                'created_at' => now(),
            ]);

            if (! empty($attachments)) {
                $this->attachMediaRecords($incident, $reporter, $attachments, $data);
            }

            AuditLog::create([
                'user_id' => $reporter?->id,
                'event_type' => $isGuest ? 'incident:guest_submitted' : 'incident:citizen_submitted',
                'auditable_type' => Incident::class,
                'auditable_id' => $incident->id,
                'new_values' => [
                    'reference_number' => $incident->reference_number,
                    'source_channel' => $incident->source_channel,
                    'workflow_stage' => 'new_report',
                    'verified_barangay_id' => $verifiedBarangay?->id,
                    'within_hinobaan_bbox' => $this->isCoordinateWithinHinobaan((float) $data['latitude'], (float) $data['longitude']),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            if ($reporter) {
                Notification::create([
                    'user_id' => $reporter->id,
                    'title' => 'Emergency Report Received',
                    'body' => "Your emergency report {$incident->reference_number} was received by DIAL DAPH Command Center. Keep your line open for verification.",
                    'type' => 'incident_submitted',
                    'action_url' => route('citizen.records.show', $incident->reference_number),
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Notify active Command Center Admins and LDRRMO Super Admins immediately
            $commandStaff = User::whereIn('account_type', ['admin', 'super_admin'])
                ->where('is_active', true)
                ->get(['id', 'account_type']);

            foreach ($commandStaff as $staff) {
                $actionUrl = $staff->account_type === 'super_admin'
                    ? route('super-admin.incidents.show', $incident->reference_number)
                    : route('admin.incidents.show', $incident->reference_number);

                Notification::create([
                    'user_id' => $staff->id,
                    'title' => $isGuest ? 'New Guest Emergency Report' : 'New Citizen Emergency Report',
                    'body' => "Unclassified report {$incident->reference_number} from {$callerName} ({$callerPhone}). Call verification required.",
                    'type' => 'emergency_report_received',
                    'action_url' => $actionUrl,
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            return [
                'incident' => $incident->load(['barangay', 'location', 'evidenceRecords.media']),
                'claim_token' => $plainClaimToken,
            ];
        });

        try {
            event(new EmergencyReportSubmitted($result['incident']));
        } catch (\Throwable) {
            // Do not fail emergency reporting if broadcast transport is offline
        }

        return $result;
    }

    /**
     * Claim verified guest emergency reports into a newly registered or authenticated citizen account.
     * Strictly requires cryptographic token verification against guest_claim_token_hash.
     *
     * @param  array<string>  $claimTokens
     */
    public function claimGuestReports(User $user, array $claimTokens): int
    {
        if (! $user->isCitizen() || $claimTokens === []) {
            return 0;
        }

        $hashes = collect($claimTokens)
            ->filter(fn (mixed $t): bool => is_string($t) && strlen($t) >= 32)
            ->map(fn (string $t): string => hash('sha256', $t))
            ->unique()
            ->values();

        if ($hashes->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($user, $hashes): int {
            $incidents = Incident::whereNull('reporter_id')
                ->where('is_guest_report', true)
                ->whereIn('guest_claim_token_hash', $hashes)
                ->get();

            $claimedCount = 0;

            foreach ($incidents as $incident) {
                $incident->update([
                    'reporter_id' => $user->id,
                    'guest_claim_token_hash' => null,
                    'guest_claimed_at' => now(),
                ]);

                MediaAttachment::where('incident_id', $incident->id)
                    ->whereNull('uploader_id')
                    ->update(['uploader_id' => $user->id]);

                AuditLog::create([
                    'user_id' => $user->id,
                    'event_type' => 'incident:guest_report_claimed',
                    'auditable_type' => Incident::class,
                    'auditable_id' => $incident->id,
                    'new_values' => ['claimed_by_user_id' => $user->id],
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_at' => now(),
                ]);

                $claimedCount++;
            }

            return $claimedCount;
        });
    }

    /**
     * Allow an authenticated citizen to update their display name and contact phone number.
     * Explicitly marks mobile number unverified when changed until verified by Command Center/OTP.
     *
     * @param  array{name: string, phone_number: string}  $data
     */
    public function updateCitizenContactProfile(User $user, array $data): User
    {
        if (! $user->isCitizen()) {
            throw new AccessDeniedHttpException('Only citizen accounts may update citizen emergency contact details.');
        }

        $newName = trim($data['name']);
        $newPhone = trim($data['phone_number']);

        return DB::transaction(function () use ($user, $newName, $newPhone): User {
            $oldValues = [
                'name' => $user->name,
                'phone_number' => $user->phone_number,
            ];

            $phoneChanged = $user->phone_number !== $newPhone;

            $user->update([
                'name' => $newName,
                'phone_number' => $newPhone,
                'is_phone_verified' => $phoneChanged ? false : $user->is_phone_verified,
            ]);

            if ($user->citizenProfile) {
                $parts = preg_split('/\s+/', $newName, 2);
                $user->citizenProfile->update([
                    'first_name' => $parts[0] ?? $newName,
                    'last_name' => $parts[1] ?? $user->citizenProfile->last_name,
                ]);
            }

            AuditLog::create([
                'user_id' => $user->id,
                'event_type' => 'citizen:contact_profile_updated',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'name' => $newName,
                    'phone_number' => $newPhone,
                    'is_phone_verified' => $user->is_phone_verified,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            return $user->fresh(['citizenProfile', 'addresses']);
        });
    }

    /**
     * Legacy programmatic submission wrapper preserved for existing internal integrations
     * when category_id is explicitly provided by internal modules; delegates simplified intake otherwise.
     *
     * @param  array<string, mixed>  $data
     * @param  array<UploadedFile>  $attachments
     */
    public function submitIncident(User $reporter, array $data, array $attachments = []): Incident
    {
        if (empty($data['category_id'])) {
            return $this->submitSimplifiedIncident($reporter, $data, $attachments)['incident'];
        }

        return DB::transaction(function () use ($reporter, $data, $attachments) {
            $referenceNumber = $this->generateReferenceNumber();

            $category = IncidentCategory::findOrFail($data['category_id']);
            $barangay = Barangay::findOrFail($data['barangay_id']);

            // Validate municipal boundaries (Hinoba-an)
            $isWithinHinobaan = $this->isCoordinateWithinHinobaan((float) $data['latitude'], (float) $data['longitude']);

            $description = $data['description'] ?? 'Emergency assistance requested via Citizen Portal.';
            if (! $isWithinHinobaan) {
                $description = '[REVIEW REQUIRED: Coordinates flagged outside standard Hinoba-an municipal boundary] '.$description;
            }

            // Create Incident (Status: always pending_review initially)
            $incident = Incident::create([
                'reference_number' => $referenceNumber,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'client_uuid' => $data['client_uuid'] ?? null,
                'reporter_id' => $reporter->id,
                'source_channel' => 'citizen_portal',
                'category_id' => $category->id,
                'priority' => $category->default_priority ?? 'high',
                'status' => 'pending_review',
                'description' => $description,
                'caller_name' => $data['caller_name'] ?? $reporter->name,
                'caller_phone' => $data['caller_phone'] ?? $reporter->phone_number,
                'barangay_id' => $barangay->id,
                'submitted_at' => now(),
                'version_lock' => 1,
            ]);

            // Map location source to database enum ('gps_device', 'map_picker', 'fb_shared_location', 'admin_entered')
            $locationSource = match ($data['location_source'] ?? 'gps_device') {
                'gps_browser', 'gps_device' => 'gps_device',
                'manual_pin', 'map_picker', 'barangay_centroid' => 'map_picker',
                'fb_shared_location' => 'fb_shared_location',
                'admin_entered' => 'admin_entered',
                default => 'gps_device',
            };

            // Save Incident Location
            IncidentLocation::create([
                'incident_id' => $incident->id,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'accuracy' => $data['accuracy'] ?? null,
                'street_address' => $data['street_address'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'location_source' => $locationSource,
            ]);

            // Save initial status history record
            IncidentStatusHistory::create([
                'incident_id' => $incident->id,
                'previous_status' => 'submitted',
                'new_status' => 'pending_review',
                'changed_by' => $reporter->id,
                'change_reason' => 'Incident reported via Citizen Portal. Queued for LDRRMO intake review.',
                'created_at' => now(),
            ]);

            // Process Media Attachments with Geo-Tagging
            if (! empty($attachments)) {
                $this->attachMediaRecords($incident, $reporter, $attachments, $data);
            }

            // Create Audit Log
            AuditLog::create([
                'user_id' => $reporter->id,
                'event_type' => 'incident:submitted',
                'auditable_type' => Incident::class,
                'auditable_id' => $incident->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
            ]);

            // Record notification for citizen
            Notification::create([
                'user_id' => $reporter->id,
                'title' => 'Incident Report Submitted',
                'body' => "Your incident report {$incident->reference_number} has been received and queued for review.",
                'type' => 'incident_submitted',
                'action_url' => route('citizen.records.show', $incident->reference_number),
                'is_read' => false,
                'created_at' => now(),
            ]);

            return $incident;
        });
    }

    /**
     * Get incident for citizen with strict ownership validation.
     */
    public function getCitizenIncident(User $reporter, int|string $identifier): ?Incident
    {
        $query = Incident::with([
            'category',
            'barangay',
            'location',
            'statusHistories.changedBy',
            'evidenceRecords.media.geoMetadata',
        ])->where('reporter_id', $reporter->id);

        if (is_numeric($identifier)) {
            return $query->where('id', (int) $identifier)->first();
        }

        return $query->where('reference_number', $identifier)->first();
    }

    /**
     * Get paginated or filtered incidents for citizen.
     */
    public function getCitizenIncidentsList(User $reporter, ?string $statusFilter = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Incident::with(['category', 'barangay', 'location'])
            ->where('reporter_id', $reporter->id)
            ->latest('submitted_at');

        if ($statusFilter && $statusFilter !== 'all') {
            $mappedStatus = match ($statusFilter) {
                'pending' => 'pending_review',
                'accepted' => 'accepted',
                'dispatched' => 'dispatched',
                'in_progress' => 'in_progress',
                'resolved' => 'resolved',
                'rejected' => 'rejected',
                default => $statusFilter,
            };

            $query->where('status', $mappedStatus);
        }

        return $query->paginate($perPage);
    }

    /**
     * Add supplementary evidence to a citizen's incident (only while still pending_review / submitted).
     * Blocks replacement/modification once accepted by LDRRMO.
     */
    public function addCitizenEvidence(User $reporter, Incident $incident, UploadedFile $file, array $data = []): MediaAttachment
    {
        if ((int) $incident->reporter_id !== (int) $reporter->id) {
            throw new AccessDeniedHttpException('Unauthorized incident access.');
        }

        if ($incident->hasLockedEvidence()) {
            throw new AccessDeniedHttpException('Official evidence cannot be replaced or modified after the incident is accepted unless an authorized evidence correction process is approved.');
        }

        $mediaService = app(MediaManagementService::class);
        $geoContext = [
            'latitude' => $data['media_lat'] ?? null,
            'longitude' => $data['media_lng'] ?? null,
            'accuracy' => $data['media_accuracy'] ?? null,
            'location_source' => $data['media_location_source'] ?? (! empty($data['media_lat']) ? MediaGeoMetadata::SOURCE_GPS_CAPTURE : null),
            'is_live_capture' => ! empty($data['media_is_live_capture']),
            'allow_incident_inherit' => true,
            'barangay_id' => $incident->barangay_id,
        ];

        return $mediaService->storeUploadedFile($file, $reporter, [
            'disk' => 'private',
            'source_channel' => 'citizen_portal',
            'allowed_categories' => ['photo', 'video'],
            'attachable' => $incident,
            'incident_id' => $incident->id,
            'link_as_evidence' => true,
            'evidence_type' => 'citizen_submission',
            'caption' => $data['caption'] ?? 'Submitted by citizen via emergency intake.',
            'geo_context' => $geoContext,
        ]);
    }

    /**
     * Process attachments through centralized Geo-Tagged Media & Evidence Management.
     *
     * @param  array<UploadedFile>  $attachments
     * @param  array<string, mixed>  $data
     */
    private function attachMediaRecords(Incident $incident, ?User $reporter, array $attachments, array $data): void
    {
        $mediaService = app(MediaManagementService::class);
        $sourceChannel = $reporter ? 'citizen_portal' : 'guest_portal';

        $perAttachmentGeo = is_array($data['attachment_geo'] ?? null) ? $data['attachment_geo'] : [];

        foreach (array_values($attachments) as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $itemGeo = is_array($perAttachmentGeo[$index] ?? null) ? $perAttachmentGeo[$index] : [];
            $itemLat = $itemGeo['latitude'] ?? $data['media_lat'] ?? null;
            $itemLng = $itemGeo['longitude'] ?? $data['media_lng'] ?? null;
            $itemAcc = $itemGeo['accuracy'] ?? $data['media_accuracy'] ?? ($data['accuracy'] ?? null);
            $isLiveCapture = ! empty($itemGeo['is_live_capture']) || ! empty($data['media_is_live_capture']);
            $itemSource = $itemGeo['location_source']
                ?? $data['media_location_source']
                ?? ($itemLat !== null && $itemLat !== '' ? ($isLiveCapture ? MediaGeoMetadata::SOURCE_GPS_CAPTURE : MediaGeoMetadata::SOURCE_UPLOAD_LOCATION) : null);

            $geoContext = [
                'latitude' => $itemLat,
                'longitude' => $itemLng,
                'accuracy' => $itemAcc,
                'location_source' => $itemSource,
                'is_live_capture' => $isLiveCapture,
                'captured_at' => $itemGeo['captured_at'] ?? null,
                'allow_incident_inherit' => true,
                'barangay_id' => $incident->barangay_id,
            ];

            $mediaService->storeUploadedFile($file, $reporter, [
                'disk' => 'private',
                'source_channel' => $sourceChannel,
                'uploader_account_type' => $reporter?->account_type ?? 'guest',
                'allowed_categories' => ['photo', 'video'],
                'attachable' => $incident,
                'incident_id' => $incident->id,
                'link_as_evidence' => true,
                'evidence_type' => 'citizen_submission',
                'caption' => $reporter ? 'Submitted by citizen via emergency intake.' : 'Submitted by guest reporter via emergency intake.',
                'geo_context' => $geoContext,
            ]);
        }
    }

    /**
     * Generate unique reference number: INC-YYYY-XXXX
     */
    private function generateReferenceNumber(): string
    {
        $year = date('Y');
        $random = strtoupper(Str::random(4));
        $count = Incident::whereYear('created_at', $year)->count() + 1;

        $reference = sprintf('INC-%s-%04d-%s', $year, $count, $random);

        // Ensure uniqueness
        while (Incident::where('reference_number', $reference)->exists()) {
            $random = strtoupper(Str::random(4));
            $reference = sprintf('INC-%s-%04d-%s', $year, $count, $random);
        }

        return $reference;
    }

    /**
     * Check if coordinates lie within Hinoba-an municipal bounding box.
     */
    public function isCoordinateWithinHinobaan(float $lat, float $lng): bool
    {
        $minLat = (float) SystemSetting::get('municipal_boundary_lat_min', 9.4000);
        $maxLat = (float) SystemSetting::get('municipal_boundary_lat_max', 9.7500);
        $minLng = (float) SystemSetting::get('municipal_boundary_lng_min', 122.3500);
        $maxLng = (float) SystemSetting::get('municipal_boundary_lng_max', 122.6500);

        return $lat >= $minLat && $lat <= $maxLat && $lng >= $minLng && $lng <= $maxLng;
    }
}
