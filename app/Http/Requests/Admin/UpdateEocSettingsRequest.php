<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEocSettingsRequest extends FormRequest
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
            'hotline_title' => ['nullable', 'string', 'max:128'],
            'hotline' => ['nullable', 'string', 'max:64'],
            'dial_daph_command_center_title' => ['nullable', 'string', 'max:128'],
            'dial_daph_command_center_hotline' => ['nullable', 'string', 'max:64'],
            'dial_daph_command_center_description' => ['nullable', 'string', 'max:255'],
            'dial_daph_command_center_active' => ['nullable', 'boolean'],
            'ldrrmo_hotline_title' => ['nullable', 'string', 'max:128'],
            'ldrrmo_hotline_number' => ['nullable', 'string', 'max:64'],
            'ldrrmo_hotline_description' => ['nullable', 'string', 'max:255'],
            'ldrrmo_hotline_active' => ['nullable', 'boolean'],
            'facebook_url' => ['required', 'url', 'max:255'],
            'lat_min' => ['required', 'numeric', 'between:-90,90'],
            'lat_max' => ['required', 'numeric', 'between:-90,90'],
            'lng_min' => ['required', 'numeric', 'between:-180,180'],
            'lng_max' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
