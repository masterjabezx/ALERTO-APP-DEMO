<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\PersonnelLocationUpdated;
use App\Models\BarangayBoundary;
use App\Models\Incident;
use App\Models\IncidentAssignment;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use App\Models\MunicipalityBoundary;
use App\Models\Office;
use App\Models\OfficeResponsibility;
use App\Models\PersonnelLocation;
use App\Models\PersonnelProfile;
use App\Models\ResponseBase;
use App\Models\ResponseTeam;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\UserPresence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LiveOperationsMapService
{
    public function __construct(
        protected GeoService $geoService
    ) {}

    /**
     * Get complete live operations dataset for Admin Live Operations Map.
     */
    public function getAdminMapData(array $filters = [], ?array $bbox = null): array
    {
        $incidentsQuery = $this->buildIncidentsQuery($filters, $bbox);
        $incidents = $incidentsQuery->get()->map(fn (Incident $i) => $this->formatIncidentMarker($i, true));
        $sosAlerts = $this->getActiveSosMarkersData($filters);

        $responders = $this->getRespondersData($filters);
        $offices = $this->getOfficesData($filters);
        $responseBases = $this->getResponseBasesData($filters);
        $evidence = $this->getGeoTaggedEvidenceData($filters);
        $boundaries = $this->getBoundariesData();
        $stats = $this->calculateMapStats();

        return [
            'incidents' => $incidents,
            'sos_alerts' => $sosAlerts,
            'responders' => $responders,
            'offices' => $offices,
            'response_bases' => $responseBases,
            'evidence' => $evidence,
            'boundaries' => $boundaries,
            'stats' => $stats,
        ];
    }

    /**
     * Get scoped operations dataset for Office Incident Map.
     */
    public function getOfficeMapData(Office $office, array $filters = []): array
    {
        $teamIds = ResponseTeam::where('office_id', $office->id)->pluck('id');
        $assignedIncidentIds = IncidentAssignment::where('office_id', $office->id)
            ->orWhereIn('team_id', $teamIds)
            ->pluck('incident_id');

        $responsibleCategories = OfficeResponsibility::where('office_id', $office->id)
            ->pluck('category_slug');

        $incidentsQuery = $this->buildIncidentsQuery($filters)
            ->where(function (Builder $q) use ($assignedIncidentIds, $responsibleCategories) {
                $q->whereIn('id', $assignedIncidentIds)
                    ->orWhereHas('category', fn ($c) => $c->whereIn('slug', $responsibleCategories));
            });

        $incidents = $incidentsQuery->get()->map(fn (Incident $i) => $this->formatIncidentMarker($i, false));

        // Responders belonging to this office (direct office assignment or via team membership)
        $personnelQuery = PersonnelProfile::with(['user.presence', 'office.location', 'availability', 'teams', 'latestLocation'])
            ->where(function ($query) use ($office) {
                $query->where('office_id', $office->id)
                    ->orWhereHas('teams', fn ($t) => $t->where('office_id', $office->id));
            });
        $responders = $this->formatRespondersCollection($personnelQuery->get());

        $offices = $this->getOfficesData(['office_id' => $office->id]);
        $responseBases = ResponseBase::where('office_id', $office->id)->where('is_active', true)->get()->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->base_name,
            'address' => $b->address,
            'latitude' => (float) $b->latitude,
            'longitude' => (float) $b->longitude,
            'phone' => $b->contact_phone,
        ]);

        $boundaries = $this->getBoundariesData();

        $scopedIncidentIds = $incidents->pluck('id')->all();
        $evidence = $this->getGeoTaggedEvidenceData(array_merge($filters, [
            'scoped_incident_ids' => $scopedIncidentIds,
        ]));

        $sosAlerts = $this->getActiveSosMarkersData(['office_id' => $office->id]);

        return [
            'office' => [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
            ],
            'incidents' => $incidents,
            'sos_alerts' => $sosAlerts,
            'responders' => $responders,
            'offices' => $offices,
            'response_bases' => $responseBases,
            'evidence' => $evidence,
            'boundaries' => $boundaries,
        ];
    }

    /**
     * Get scoped operations dataset for Personnel Incident Map.
     */
    public function getPersonnelMapData(User $user, array $filters = []): array
    {
        $personnel = $user->personnelProfile;
        if (! $personnel) {
            return ['incidents' => [], 'responders' => [], 'routes' => []];
        }

        // 1. Assigned incidents
        $assignedIncidentIds = IncidentAssignment::where('personnel_id', $personnel->id)->pluck('incident_id');

        // 2. Authorized jurisdiction barangays
        $jurisdictionBarangayIds = $personnel->jurisdictions()->pluck('barangay_id');

        // Scope to active incidents in jurisdiction OR assigned to this personnel
        $incidentsQuery = $this->buildIncidentsQuery($filters)
            ->where(function (Builder $q) use ($assignedIncidentIds, $jurisdictionBarangayIds) {
                $q->whereIn('id', $assignedIncidentIds)
                    ->orWhere(function ($sub) use ($jurisdictionBarangayIds) {
                        $sub->whereIn('barangay_id', $jurisdictionBarangayIds)
                            ->whereIn('status', ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress']);
                    });
            });

        $incidents = $incidentsQuery->get()->map(fn (Incident $i) => $this->formatIncidentMarker($i, false));

        // Active personal route if dispatched to an incident
        $routes = [];
        $activeAssignment = IncidentAssignment::with('incident.location')
            ->where('personnel_id', $personnel->id)
            ->whereIn('status', ['active', 'dispatched', 'acknowledged', 'on_scene'])
            ->latest('assigned_at')
            ->first();

        if ($activeAssignment && $activeAssignment->incident?->location) {
            $destLat = (float) $activeAssignment->incident->location->latitude;
            $destLng = (float) $activeAssignment->incident->location->longitude;

            // Use latest personnel location or assigned office base
            $lastLoc = $personnel->latestLocation ?? $personnel->locations()->latest('recorded_at')->first();
            $officeLoc = $personnel->office?->location;
            $originLat = $lastLoc
                ? (float) $lastLoc->latitude
                : ($officeLoc ? (float) $officeLoc->latitude : 9.4975);
            $originLng = $lastLoc
                ? (float) $lastLoc->longitude
                : ($officeLoc ? (float) $officeLoc->longitude : 122.5850);

            $routes[] = array_merge(
                $this->geoService->calculateRoute($originLat, $originLng, $destLat, $destLng),
                [
                    'incident_id' => $activeAssignment->incident_id,
                    'reference_number' => $activeAssignment->incident->reference_number,
                ]
            );
        }

        $scopedIncidentIds = $incidents->pluck('id')->all();
        $evidence = $this->getGeoTaggedEvidenceData(array_merge($filters, [
            'scoped_incident_ids' => $scopedIncidentIds,
        ]));

        return [
            'personnel' => [
                'id' => $personnel->id,
                'name' => $user->name,
                'badge' => $personnel->badge_number,
            ],
            'incidents' => $incidents,
            'evidence' => $evidence,
            'routes' => $routes,
            'boundaries' => $this->getBoundariesData(),
        ];
    }

    /**
     * Record new GPS location from authenticated personnel.
     */
    public function recordPersonnelLocation(
        PersonnelProfile $personnel,
        float $latitude,
        float $longitude,
        ?float $heading = null,
        ?float $speed = null,
        ?float $accuracy = null
    ): PersonnelLocation {
        // Enforce duty check: Stop tracking if offline/off-duty
        $availability = $personnel->availability;
        if ($availability && $availability->operational_status === 'off_duty') {
            throw new \InvalidArgumentException('Location sharing cannot be updated while responder is off duty.');
        }

        return DB::transaction(function () use ($personnel, $latitude, $longitude, $heading, $speed, $accuracy) {
            $location = PersonnelLocation::create([
                'personnel_id' => $personnel->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'heading' => $heading,
                'speed' => $speed,
                'accuracy' => $accuracy,
                'recorded_at' => now(),
                'created_at' => now(),
            ]);

            // Update user presence and availability heartbeats
            UserPresence::updateOrCreate(
                ['user_id' => $personnel->user_id],
                [
                    'status' => 'online',
                    'last_heartbeat_at' => now(),
                ]
            );

            if ($personnel->availability) {
                $personnel->availability->update(['last_heartbeat_at' => now()]);
            }

            // Broadcast real-time location update event
            event(new PersonnelLocationUpdated(
                $personnel,
                $latitude,
                $longitude,
                $heading,
                $speed,
                $accuracy
            ));

            return $location;
        });
    }

    /**
     * Format incident into rich Map Marker representation.
     */
    public function formatIncidentMarker(Incident $incident, bool $isAdmin = true): array
    {
        $visualKey = $incident->workflow_stage ?: $incident->status;
        $statusVisuals = $this->getStatusVisuals((string) $visualKey);
        $loc = $incident->location;

        $assignments = $incident->assignments->map(fn (IncidentAssignment $a) => [
            'id' => $a->id,
            'office' => $a->office?->name,
            'team' => $a->team?->name,
            'personnel' => $a->personnel?->user?->name,
            'status' => $a->status,
        ]);

        $evidence = $incident->evidenceRecords->map(fn ($ev) => [
            'id' => $ev->id,
            'media_id' => $ev->media?->id,
            'type' => $ev->evidence_type,
            'caption' => $ev->caption,
            'file_name' => $ev->media?->file_name,
            'mime_type' => $ev->media?->mime_type,
            'media_type' => $ev->media?->media_type,
            'stream_url' => $ev->media ? route('media.stream', $ev->media->id) : null,
            'latitude' => $ev->media?->geoMetadata?->latitude ? (float) $ev->media->geoMetadata->latitude : null,
            'longitude' => $ev->media?->geoMetadata?->longitude ? (float) $ev->media->geoMetadata->longitude : null,
            'gps_source' => $ev->media?->geoMetadata?->gps_source ?? 'none',
            'location_source' => $ev->media?->geoMetadata?->location_source ?? MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE,
            'is_inherited' => (bool) ($ev->media?->geoMetadata?->is_inherited ?? false),
        ]);

        return [
            'id' => $incident->id,
            'reference_number' => $incident->reference_number,
            'category' => $incident->category?->name ?? 'Unclassified Emergency',
            'category_slug' => $incident->category?->slug ?? 'unclassified',
            'priority' => $incident->priority ?? 'unassigned',
            'status' => $incident->status,
            'workflow_stage' => $incident->workflow_stage,
            'assessment_status' => $incident->assessment_status,
            'is_guest_report' => (bool) $incident->is_guest_report,
            'has_assignment_warning' => (bool) $incident->has_assignment_warning,
            'assignment_warning_message' => $incident->assignment_warning_message,
            'processed_by_admin' => $incident->processedByAdmin?->name,
            'description' => $incident->description ?: 'Unclassified report awaiting Command Center verification call.',
            'barangay' => $incident->barangay?->name ?? 'Unverified Location',
            'latitude' => $loc ? (float) $loc->latitude : 9.4975,
            'longitude' => $loc ? (float) $loc->longitude : 122.5850,
            'accuracy' => $loc ? (float) $loc->accuracy : null,
            'location_source' => $loc?->location_source ?? 'reported_pin',
            'street_name' => $loc?->street_address ?? $loc?->street_name,
            'submitted_at' => $incident->submitted_at?->toIso8601String(),
            'formatted_time' => $incident->submitted_at?->diffForHumans() ?? 'Just now',
            'marker' => [
                'color' => $statusVisuals['color'],
                'icon' => $statusVisuals['icon'],
                'label' => $statusVisuals['label'],
            ],
            'caller' => $isAdmin ? [
                'name' => $incident->caller_name ?? 'Emergency Caller',
                'phone' => $incident->caller_phone ?? 'Unlisted',
                'source' => $incident->source_channel,
                'verification_status' => $incident->contact_verification_status ?? 'unverified',
            ] : null,
            'assignments' => $assignments,
            'evidence' => $evidence,
        ];
    }

    /**
     * Map workflow stage & operational status visual definitions (Color, Icon, Accessible Text Label).
     */
    public function getStatusVisuals(string $status): array
    {
        return match ($status) {
            'new_report', 'submitted', 'pending_review' => [
                'color' => '#F97316', // Vibrant Amber/Orange
                'icon' => 'alert-triangle',
                'label' => 'New Report (Unclassified)',
            ],
            'awaiting_call' => [
                'color' => '#EAB308', // Yellow
                'icon' => 'phone-call',
                'label' => 'Awaiting Call Verification',
            ],
            'assessment_in_progress' => [
                'color' => '#2563EB', // Blue
                'icon' => 'clipboard-list',
                'label' => 'Assessment In Progress',
            ],
            'classified_ready', 'accepted' => [
                'color' => '#4F46E5', // Indigo
                'icon' => 'check-circle',
                'label' => 'Classified & Accepted',
            ],
            'dispatched' => [
                'color' => '#10B981', // Green
                'icon' => 'send',
                'label' => 'Dispatched',
            ],
            'active_response', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress' => [
                'color' => '#059669', // Deep Teal/Green
                'icon' => 'navigation',
                'label' => 'Active Response',
            ],
            'resolved' => [
                'color' => '#6B7280', // Gray
                'icon' => 'check',
                'label' => 'Resolved',
            ],
            'closed' => [
                'color' => '#4B5563', // Dark Gray
                'icon' => 'archive',
                'label' => 'Closed',
            ],
            'rejected' => [
                'color' => '#EF4444', // Red
                'icon' => 'x-circle',
                'label' => 'Rejected',
            ],
            default => [
                'color' => '#9CA3AF',
                'icon' => 'alert-circle',
                'label' => ucfirst(str_replace('_', ' ', $status)),
            ],
        };
    }

    /**
     * Format distinct active SOS alert markers (Citizen, Guest, Personnel distress).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getActiveSosMarkersData(array $filters = []): array
    {
        $query = SosAlert::with(['barangay', 'office', 'user', 'personnel.user'])
            ->active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest('triggered_at');

        if (! empty($filters['office_id'])) {
            $officeId = (int) $filters['office_id'];
            $query->where(function (Builder $q) use ($officeId): void {
                $q->where('office_id', $officeId)
                    ->orWhereHas('assignments', fn (Builder $aq) => $aq->where('office_id', $officeId));
            });
        }

        return $query->limit(50)->get()->map(function (SosAlert $sos): array {
            $isPersonnel = $sos->sender_type === 'personnel';

            return [
                'id' => $sos->id,
                'reference_number' => $sos->reference_number,
                'sender_type' => $sos->sender_type,
                'sender_name' => $sos->sender_name,
                'sender_phone' => $sos->sender_phone,
                'status' => $sos->status,
                'location_status' => $sos->location_status,
                'headline' => $sos->headline(),
                'barangay' => $sos->barangay?->name ?? 'Unverified Location',
                'office_name' => $sos->office?->name,
                'latitude' => (float) $sos->latitude,
                'longitude' => (float) $sos->longitude,
                'accuracy' => $sos->accuracy !== null ? (float) $sos->accuracy : null,
                'triggered_at' => $sos->triggered_at?->toIso8601String(),
                'formatted_time' => $sos->triggered_at?->diffForHumans() ?? 'Just now',
                'marker' => [
                    'color' => $isPersonnel ? '#991B1B' : '#DC2626',
                    'icon' => 'siren',
                    'label' => $isPersonnel ? 'PERSONNEL SOS DISTRESS' : 'CITIZEN SOS EMERGENCY',
                    'pulse' => true,
                ],
            ];
        })->toArray();
    }

    /**
     * Build base filtered incident query.
     */
    protected function buildIncidentsQuery(array $filters = [], ?array $bbox = null): Builder
    {
        $query = Incident::with([
            'category',
            'barangay',
            'location',
            'processedByAdmin',
            'assignments.office',
            'assignments.team',
            'assignments.personnel.user',
            'contactVerifications',
            'evidenceRecords.media.geoMetadata',
        ])->latest('submitted_at');

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['barangay_id'])) {
            $query->where('barangay_id', $filters['barangay_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['office_id'])) {
            $query->whereHas('assignments', fn ($a) => $a->where('office_id', $filters['office_id']));
        }

        if (! empty($filters['team_id'])) {
            $query->whereHas('assignments', fn ($a) => $a->where('team_id', $filters['team_id']));
        }

        if (! empty($filters['personnel_id'])) {
            $query->whereHas('assignments', fn ($a) => $a->where('personnel_id', $filters['personnel_id']));
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('caller_name', 'like', "%{$search}%")
                    ->orWhereHas('barangay', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        // Bounding box filter
        if ($bbox && isset($bbox['min_lat'], $bbox['max_lat'], $bbox['min_lng'], $bbox['max_lng'])) {
            $query->whereHas('location', function ($loc) use ($bbox) {
                $loc->whereBetween('latitude', [$bbox['min_lat'], $bbox['max_lat']])
                    ->whereBetween('longitude', [$bbox['min_lng'], $bbox['max_lng']]);
            });
        }

        return $query;
    }

    /**
     * Format responders list with telemetry and freshness flags.
     */
    protected function getRespondersData(array $filters = []): array
    {
        $query = PersonnelProfile::with([
            'user.presence',
            'availability',
            'teams.office',
            'latestLocation',
        ]);

        if (! empty($filters['personnel_status'])) {
            $status = $filters['personnel_status'];
            if (in_array($status, ['online', 'offline', 'connection_lost'], true)) {
                $query->whereHas('user.presence', fn ($p) => $p->where('status', $status));
            } else {
                $query->whereHas('availability', fn ($a) => $a->where('operational_status', $status));
            }
        }

        return $this->formatRespondersCollection($query->get());
    }

    protected function formatRespondersCollection($personnels): array
    {
        return $personnels->map(function (PersonnelProfile $p) {
            $lastLoc = $p->relationLoaded('latestLocation')
                ? $p->latestLocation
                : $p->latestLocation()->first();
            $presence = $p->user?->presence;
            $availability = $p->availability;
            $team = $p->teams->first();

            $freshness = 'unavailable';
            if ($lastLoc && $lastLoc->recorded_at) {
                $diffSeconds = abs((int) now()->diffInSeconds($lastLoc->recorded_at));
                if ($diffSeconds < 120) {
                    $freshness = 'live';
                } elseif ($diffSeconds < 300) {
                    $freshness = 'recently_updated';
                } else {
                    $freshness = 'stale';
                }
            }

            return [
                'id' => $p->id,
                'name' => $p->user?->name ?? 'Responder',
                'badge' => $p->badge_number,
                'office' => $team?->office?->name ?? 'Emergency Agency',
                'team' => $team?->name,
                'presence_status' => $presence?->status ?? 'offline',
                'operational_status' => $availability?->operational_status ?? 'off_duty',
                'freshness' => $freshness,
                'latitude' => $lastLoc ? (float) $lastLoc->latitude : null,
                'longitude' => $lastLoc ? (float) $lastLoc->longitude : null,
                'accuracy' => $lastLoc ? (float) $lastLoc->accuracy : null,
                'heading' => $lastLoc ? (float) $lastLoc->heading : null,
                'speed' => $lastLoc ? (float) $lastLoc->speed : null,
                'last_update' => $lastLoc?->recorded_at?->diffForHumans() ?? 'No telemetry',
            ];
        })->toArray();
    }

    protected function getOfficesData(array $filters = []): array
    {
        $query = Office::with('location')->where('is_active', true);
        if (! empty($filters['office_id'])) {
            $query->where('id', $filters['office_id']);
        }

        return $query->get()->map(fn (Office $o) => [
            'id' => $o->id,
            'name' => $o->name,
            'code' => $o->code,
            'contact' => $o->contact_phone,
            'email' => $o->contact_email,
            'latitude' => $o->location ? (float) $o->location->latitude : null,
            'longitude' => $o->location ? (float) $o->location->longitude : null,
        ])->toArray();
    }

    protected function getResponseBasesData(array $filters = []): array
    {
        return ResponseBase::with('office')->where('is_active', true)->get()->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->base_name,
            'office' => $b->office?->name,
            'address' => $b->address,
            'latitude' => (float) $b->latitude,
            'longitude' => (float) $b->longitude,
            'phone' => $b->contact_phone,
        ])->toArray();
    }

    protected function getGeoTaggedEvidenceData(array $filters = []): array
    {
        if (array_key_exists('scoped_incident_ids', $filters)) {
            $scopedIds = $filters['scoped_incident_ids'];
            if (empty($scopedIds)) {
                return [];
            }
        }

        $query = MediaAttachment::with([
            'geoMetadata.barangay',
            'uploader',
            'incident.category',
            'evidenceRecords.incident.category',
        ])
            ->where('is_archived', false)
            ->whereIn('media_type', ['photo', 'video'])
            ->whereHas('geoMetadata', function (Builder $g): void {
                $g->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereRaw('(ABS(latitude) >= 0.00001 OR ABS(longitude) >= 0.00001)');
            });

        if (isset($filters['scoped_incident_ids'])) {
            $scopedIds = $filters['scoped_incident_ids'];
            $query->where(function (Builder $q) use ($scopedIds): void {
                $q->whereIn('incident_id', $scopedIds)
                    ->orWhereHas('evidenceRecords', fn (Builder $e) => $e->whereIn('incident_id', $scopedIds));
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
            $query->whereHas('geoMetadata', fn (Builder $g) => $g->where('barangay_id', $brgyId));
        }

        return $query->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (MediaAttachment $m): array {
                $geo = $m->geoMetadata;
                $incident = $m->incident ?? $m->evidenceRecords->first()?->incident;
                $uploaderRole = $m->uploader_account_type ?? $m->uploader?->account_type ?? 'citizen';
                $mediaType = $m->isVideo() ? 'video' : 'photo';

                $markerStyle = match (true) {
                    $uploaderRole === 'personnel' => 'personnel_evidence',
                    $uploaderRole === 'office' => 'office_evidence',
                    $mediaType === 'video' => 'video_evidence',
                    default => 'photo_evidence',
                };

                return [
                    'id' => $m->id,
                    'file_name' => $m->file_name,
                    'mime_type' => $m->mime_type,
                    'media_type' => $mediaType,
                    'source_channel' => $m->source_channel,
                    'uploader' => $m->uploader?->name ?? 'External / Facebook Citizen',
                    'uploader_account_type' => $uploaderRole,
                    'marker_style' => $markerStyle,
                    'incident_id' => $incident?->id,
                    'incident_reference' => $incident?->reference_number,
                    'incident_category' => $incident?->category?->name,
                    'stream_url' => route('media.stream', $m->id),
                    'thumbnail_url' => route('media.thumbnail', $m->id),
                    'latitude' => (float) $geo->latitude,
                    'longitude' => (float) $geo->longitude,
                    'accuracy' => $geo->accuracy_meters !== null ? (float) $geo->accuracy_meters : null,
                    'gps_source' => $geo->gps_source,
                    'location_source' => $geo->location_source ?? MediaGeoMetadata::SOURCE_LOCATION_UNAVAILABLE,
                    'is_inherited' => (bool) $geo->is_inherited,
                    'barangay' => $geo->barangay?->name,
                    'captured_at' => $geo->captured_at?->toIso8601String(),
                    'uploaded_at' => ($geo->uploaded_at ?? $m->created_at)?->toIso8601String(),
                ];
            })
            ->toArray();
    }

    protected function getBoundariesData(): array
    {
        $municipalityBoundary = MunicipalityBoundary::where('is_verified', true)->first();
        $barangayBoundaries = BarangayBoundary::with('barangay')->where('is_verified', true)->get();

        return [
            'municipality' => $municipalityBoundary ? json_decode((string) $municipalityBoundary->geojson_boundary, true) : null,
            'barangays' => $barangayBoundaries->map(fn ($b) => [
                'barangay_id' => $b->barangay_id,
                'name' => $b->barangay?->name,
                'geojson' => json_decode((string) $b->geojson_boundary, true),
            ])->toArray(),
        ];
    }

    protected function calculateMapStats(): array
    {
        return [
            'total_incidents' => Incident::count(),
            'pending_review' => Incident::whereIn('status', ['submitted', 'pending_review'])->count(),
            'incoming_reports' => Incident::incomingReports()->count(),
            'active_sos' => SosAlert::active()->count(),
            'active_incidents' => Incident::whereIn('status', ['accepted', 'dispatched', 'acknowledged', 'on_the_way', 'on_scene', 'in_progress'])->count(),
            'resolved_incidents' => Incident::where('status', 'resolved')->count(),
            'online_personnel' => UserPresence::where('status', 'online')->count(),
            'active_teams' => ResponseTeam::where('is_active', true)->count(),
        ];
    }
}
