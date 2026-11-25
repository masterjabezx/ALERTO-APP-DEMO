<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
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
            'max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:500'],
            'max_upload_count' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }
}
