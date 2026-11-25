<?php

declare(strict_types=1);

namespace App\Http\Requests\Messenger;

use App\Services\MediaMetadataService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ! $user->isCitizen();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mediaConfig = app(MediaMetadataService::class)->getUploadPolicyConfiguration();
        $maxKb = max(
            $mediaConfig['max_photo_size_mb'],
            $mediaConfig['max_video_size_mb'],
            $mediaConfig['max_document_size_mb']
        ) * 1024;
        $allowedExtensions = implode(',', array_unique(array_merge(
            $mediaConfig['allowed_photo_types'],
            $mediaConfig['allowed_video_types'],
            $mediaConfig['allowed_document_types']
        )));

        return [
            'body' => [
                'nullable',
                'string',
                'max:5000',
                'required_without_all:attachment,attachments',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:'.$maxKb,
                'mimes:'.$allowedExtensions,
                'required_without_all:body,attachments',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:'.$mediaConfig['max_upload_count'],
            ],
            'attachments.*' => [
                'file',
                'max:'.$maxKb,
                'mimes:'.$allowedExtensions,
            ],
            'incident_id' => ['nullable', 'integer', 'exists:incidents,id'],
            'reply_to_message_id' => ['nullable', 'integer', 'exists:messages,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'gps_source' => ['nullable', 'string', 'in:exif_embedded,device_live_upload,incident_inherited,none,GPS_CAPTURE,EXIF_CAPTURE,UPLOAD_LOCATION,INCIDENT_INHERITED_LOCATION,LOCATION_UNAVAILABLE'],
            'is_live_capture' => ['nullable', 'boolean'],
            'inherit_incident_location' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['attachment', 'attachments', 'attachments.*'])) {
                    return;
                }

                $metadataService = app(MediaMetadataService::class);

                $single = $this->file('attachment');
                if ($single instanceof UploadedFile) {
                    try {
                        $metadataService->validateUploadedFile($single, ['photo', 'video', 'document']);
                    } catch (\InvalidArgumentException $e) {
                        $validator->errors()->add('attachment', $e->getMessage());
                    }
                }

                $multi = $this->file('attachments', []);
                if (is_array($multi)) {
                    foreach ($multi as $idx => $file) {
                        if ($file instanceof UploadedFile) {
                            try {
                                $metadataService->validateUploadedFile($file, ['photo', 'video', 'document']);
                            } catch (\InvalidArgumentException $e) {
                                $validator->errors()->add("attachments.{$idx}", $e->getMessage());
                            }
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
            'body.required_without_all' => 'Please provide a message or attach a file.',
            'attachment.required_without_all' => 'Please provide a message or attach a file.',
            'attachment.max' => 'The file size exceeds the configured limit.',
            'attachment.mimes' => 'The uploaded file format is not authorized.',
        ];
    }
}
