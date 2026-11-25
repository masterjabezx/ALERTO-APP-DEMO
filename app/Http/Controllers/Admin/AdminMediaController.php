<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArchiveMediaRequest;
use App\Http\Requests\Admin\ConfirmMediaLocationRequest;
use App\Http\Requests\Admin\PurgeMediaRequest;
use App\Http\Requests\Admin\UpdateMediaSettingsRequest;
use App\Jobs\ProcessMediaAttachmentJob;
use App\Models\Barangay;
use App\Models\IncidentCategory;
use App\Models\MediaAttachment;
use App\Models\MediaGeoMetadata;
use App\Models\User;
use App\Services\MediaGeotagService;
use App\Services\MediaManagementService;
use App\Services\MediaMetadataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminMediaController extends Controller
{
    public function __construct(
        protected MediaManagementService $mediaService,
        protected MediaMetadataService $metadataService,
        protected MediaGeotagService $geotagService
    ) {}

    /**
     * ADMIN -> MEDIA AND EVIDENCE MANAGEMENT: Centralized Governance Console.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MediaAttachment::class);

        $filters = $request->only([
            'media_type',
            'source_channel',
            'processing_status',
            'location_source',
            'uploader_id',
            'incident_reference',
            'category_id',
            'barangay_id',
            'date_from',
            'date_to',
            'archived',
            'search',
        ]);

        $mediaItems = $this->mediaService->getFilteredMedia($filters, 20, false);
        $analytics = $this->mediaService->getDashboardAnalytics();
        $policyConfig = $this->metadataService->getUploadPolicyConfiguration();

        $barangays = Barangay::orderBy('name')->get();
        $uploaders = User::whereHas('uploadedMedia')
            ->orWhereIn('account_type', ['admin', 'office', 'personnel'])
            ->orderBy('name')
            ->get();

        return view('admin.media.index', [
            'mediaItems' => $mediaItems,
            'analytics' => $analytics,
            'policyConfig' => $policyConfig,
            'filters' => $filters,
            'barangays' => $barangays,
            'uploaders' => $uploaders,
            'locationSources' => MediaGeoMetadata::LOCATION_SOURCES,
        ]);
    }

    /**
     * ADMIN -> INCIDENT MANAGEMENT -> EVIDENCE VIEWER.
     */
    public function evidenceViewer(Request $request): View
    {
        Gate::authorize('viewAny', MediaAttachment::class);

        $filters = $request->only([
            'media_type',
            'source_channel',
            'location_source',
            'uploader_id',
            'incident_reference',
            'category_id',
            'barangay_id',
            'date_from',
            'date_to',
            'search',
        ]);

        $evidenceItems = $this->mediaService->getFilteredMedia($filters, 18, true);
        $categories = IncidentCategory::where('is_active', true)->orderBy('name')->get();
        $barangays = Barangay::orderBy('name')->get();
        $uploaders = User::whereHas('uploadedMedia')->orderBy('name')->get();

        return view('admin.incidents.evidence', [
            'evidenceItems' => $evidenceItems,
            'filters' => $filters,
            'categories' => $categories,
            'barangays' => $barangays,
            'uploaders' => $uploaders,
            'locationSources' => MediaGeoMetadata::LOCATION_SOURCES,
        ]);
    }

    /**
     * Return full forensic metadata & integrity inspection for a single media item.
     */
    public function show(MediaAttachment $media): JsonResponse
    {
        Gate::authorize('view', $media);

        $media->load([
            'uploader',
            'archiver',
            'geoMetadata.barangay',
            'incident.category',
            'incident.barangay',
            'conversation',
            'evidenceRecords.incident',
        ]);

        return response()->json([
            'media' => $media,
            'integrity_verified' => $this->mediaService->verifyFileIntegrity($media),
            'stream_url' => route('media.stream', $media->id),
            'download_url' => route('media.download', $media->id),
            'formatted_size' => $media->formattedSize(),
        ]);
    }

    /**
     * Update Admin configurable media restriction & retention settings.
     */
    public function updateSettings(UpdateMediaSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('manageSettings', MediaAttachment::class);

        /** @var User $admin */
        $admin = Auth::user();

        $this->mediaService->updateSettings($request->validated(), $admin);

        return redirect()->route('admin.media.index')->with('success', 'Centralized media upload limits and retention policies updated.');
    }

    /**
     * Audited evidence archive action (never destroys operational files).
     */
    public function archive(ArchiveMediaRequest $request, MediaAttachment $media): RedirectResponse
    {
        Gate::authorize('archive', $media);

        /** @var User $admin */
        $admin = Auth::user();

        $this->mediaService->archiveMedia($media, $admin, $request->validated('reason'));

        return back()->with('success', "Evidence item #{$media->id} formally archived in audit log.");
    }

    /**
     * Audited retention purge (only after archival & case closure).
     */
    public function purge(PurgeMediaRequest $request, MediaAttachment $media): RedirectResponse
    {
        Gate::authorize('purge', $media);

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $this->mediaService->auditedRetentionPurge($media, $admin, $request->validated('justification'));

            return back()->with('success', "Archived media item #{$media->id} disposed under audited retention policy.");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Retry queued processing for failed or pending attachments.
     */
    public function retryProcessing(MediaAttachment $media): RedirectResponse
    {
        Gate::authorize('viewAny', MediaAttachment::class);

        $media->update([
            'processing_status' => 'uploaded',
            'processing_error' => null,
        ]);

        ProcessMediaAttachmentJob::dispatch($media->id);

        return back()->with('success', "Media processing re-queued for attachment #{$media->id}.");
    }

    /**
     * Admin explicitly confirms coordinates on map for evidence item.
     */
    public function confirmLocation(ConfirmMediaLocationRequest $request, MediaAttachment $media): RedirectResponse
    {
        Gate::authorize('view', $media);

        $validated = $request->validated();

        $this->geotagService->adminConfirmLocation(
            $media,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['barangay_id']) ? (int) $validated['barangay_id'] : null
        );

        return back()->with('success', 'Evidence geographic coordinates explicitly confirmed by EOC Admin.');
    }
}
