<?php

declare(strict_types=1);

namespace App\Http\Requests\Citizen;

use App\Services\MediaMetadataService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class SubmitIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->account_type === 'citizen';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
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
            'category_id' => ['required', 'exists:incident_categories,id'],
            'caller_phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'caller_name' => ['nullable', 'string', 'max:128'],
            'barangay_id' => ['required', 'exists:barangays,id'],
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

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['attachments', 'attachments.*'])) {
                    return;
                }

                $files = $this->file('attachments', []);
                if (! is_array($files)) {
                    $files = $files ? [$files] : [];
                }

                $metadataService = app(MediaMetadataService::class);
                foreach ($files as $idx => $file) {
                    if ($file instanceof UploadedFile) {
                        try {
                            $metadataService->validateUploadedFile($file, ['photo', 'video']);
                        } catch (\InvalidArgumentException $e) {
                            $validator->errors()->add("attachments.{$idx}", $e->getMessage());
                        }
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select an incident emergency category.',
            'category_id.exists' => 'The selected emergency category is invalid.',
            'caller_phone.required' => 'A valid contact number is required so responders can verify the situation.',
            'caller_phone.regex' => 'The contact number must be a valid 11-digit Philippine mobile number (e.g. 09171234567).',
            'barangay_id.required' => 'Please select or confirm the barangay location.',
            'latitude.required' => 'An incident location coordinate is required.',
            'longitude.required' => 'An incident location coordinate is required.',
            'attachments.max' => 'You may attach up to the configured maximum number of photos or videos.',
            'attachments.*.max' => 'An attached file exceeds the maximum allowed upload size.',
            'attachments.*.mimes' => 'Attached files must be in an authorized image or video format.',
        ];
    }
}
