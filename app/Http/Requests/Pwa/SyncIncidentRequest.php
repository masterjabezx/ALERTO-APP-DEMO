<?php

declare(strict_types=1);

namespace App\Http\Requests\Pwa;

use App\Services\MediaMetadataService;
use Illuminate\Foundation\Http\FormRequest;

class SyncIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->account_type, ['citizen', 'admin', 'super_admin'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mediaConfig = app(MediaMetadataService::class)->getUploadPolicyConfiguration();
        $maxPhotoVideoMb = max($mediaConfig['max_photo_size_mb'], $mediaConfig['max_video_size_mb']);
        $maxKb = $maxPhotoVideoMb * 1024;
        $maxCount = $mediaConfig['max_upload_count'];

        $allowedExtensions = implode(',', array_unique(array_merge(
            $mediaConfig['allowed_photo_types'],
            $mediaConfig['allowed_video_types']
        )));

        return [
            'idempotency_key' => ['required', 'string', 'max:64'],
            'client_uuid' => ['nullable', 'string', 'max:64'],
            'category_id' => ['nullable', 'exists:incident_categories,id'],
            'caller_phone' => ['required', 'string', 'min:7', 'max:24', 'regex:/^[0-9+\-\s()]{7,24}$/'],
            'caller_name' => ['nullable', 'string', 'max:128'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'location_source' => ['required', 'in:gps_device,map_picker,gps_browser,manual_pin,barangay_centroid'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:'.$maxCount],
            'attachments.*' => [
                'file',
                'mimes:'.$allowedExtensions,
                'max:'.$maxKb,
            ],
            'media_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'media_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'media_accuracy' => ['nullable', 'numeric', 'min:0'],
            'media_location_source' => ['nullable', 'string', 'max:64'],
            'media_is_live_capture' => ['nullable', 'boolean'],
        ];
    }
}
