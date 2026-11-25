<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'max_photo_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_video_size_mb' => ['required', 'integer', 'min:1', 'max:500'],
            'max_document_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'max_upload_count' => ['required', 'integer', 'min:1', 'max:20'],
            'allowed_photo_types' => ['required', 'string', 'max:255'],
            'allowed_video_types' => ['required', 'string', 'max:255'],
            'allowed_document_types' => ['required', 'string', 'max:255'],
            'retention_days' => ['required', 'integer', 'min:30', 'max:3650'],
            'auto_generate_thumbnails' => ['nullable', 'boolean'],
            'malware_scan_enabled' => ['nullable', 'boolean'],
        ];
    }
}
