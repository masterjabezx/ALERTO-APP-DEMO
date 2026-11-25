<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\MediaAttachment;
use App\Models\User;
use App\Services\CitizenIncidentService;
use App\Services\MediaMetadataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MediaController extends Controller
{
    /**
     * Stream media file inline securely after policy verification (never publicly exposed).
     */
    public function stream(MediaAttachment $media): Response|StreamedResponse
    {
        Gate::authorize('view', $media);

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->file_path)) {
            abort(404, 'Media file not found on storage.');
        }

        $headers = [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($media->file_name).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ];

        $absolutePath = $disk->path($media->file_path);
        if (is_file($absolutePath)) {
            return response()->file($absolutePath, $headers);
        }

        return response($disk->get($media->file_path), 200, $headers);
    }

    /**
     * Stream generated thumbnail image when available (falls back safely to inline photo).
     */
    public function thumbnail(MediaAttachment $media): Response|StreamedResponse
    {
        Gate::authorize('view', $media);

        $disk = Storage::disk($media->disk);

        if ($media->thumbnail_path && $disk->exists($media->thumbnail_path)) {
            $headers = [
                'Content-Type' => 'image/jpeg',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=3600',
            ];
            $thumbPath = $disk->path($media->thumbnail_path);
            if (is_file($thumbPath)) {
                return response()->file($thumbPath, $headers);
            }

            return response($disk->get($media->thumbnail_path), 200, $headers);
        }

        if ($media->isPhoto()) {
            return $this->stream($media);
        }

        abort(404, 'Thumbnail not available for this media item.');
    }

    /**
     * Download original preserved evidence file securely.
     */
    public function download(MediaAttachment $media): StreamedResponse|Response
    {
        Gate::authorize('download', $media);

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->file_path)) {
            abort(404, 'Media file not found on storage.');
        }

        return $disk->download($media->file_path, $media->file_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Allow Citizen to attach supplementary evidence ONLY before incident acceptance.
     */
    public function addCitizenEvidence(Request $request, string $reference, CitizenIncidentService $citizenService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $incident = Incident::where('reference_number', $reference)->firstOrFail();

        if ($incident->hasLockedEvidence()) {
            abort(403, 'Official evidence cannot be replaced or added after incident acceptance unless an authorized evidence correction process is approved.');
        }

        $mediaConfig = app(MediaMetadataService::class)->getUploadPolicyConfiguration();
        $maxKb = max($mediaConfig['max_photo_size_mb'], $mediaConfig['max_video_size_mb']) * 1024;
        $allowedExt = implode(',', array_unique(array_merge($mediaConfig['allowed_photo_types'], $mediaConfig['allowed_video_types'])));

        $request->validate([
            'attachment' => ['required', 'file', 'mimes:'.$allowedExt, 'max:'.$maxKb],
            'caption' => ['nullable', 'string', 'max:255'],
            'media_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'media_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'media_accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $citizenService->addCitizenEvidence($user, $incident, $request->file('attachment'), $request->all());

            return back()->with('success', 'Supplementary evidence added cleanly to incident record.');
        } catch (AccessDeniedHttpException $e) {
            abort(403, $e->getMessage());
        }
    }

    /**
     * Prevent citizens from deleting/replacing official evidence once accepted.
     */
    public function deleteCitizenEvidence(string $reference, MediaAttachment $media): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $incident = Incident::where('reference_number', $reference)->firstOrFail();

        if ((int) $incident->reporter_id !== (int) $user->id) {
            abort(403, 'Unauthorized.');
        }

        if ($incident->hasLockedEvidence() || $media->is_locked_evidence) {
            abort(403, 'Official evidence cannot be deleted or replaced after the incident has been accepted.');
        }

        // Preserve original file in system; unlink only pending review unaccepted evidence
        $incident->evidenceRecords()->where('media_id', $media->id)->delete();

        return back()->with('success', 'Pending attachment unlinked from incident preview.');
    }
}
