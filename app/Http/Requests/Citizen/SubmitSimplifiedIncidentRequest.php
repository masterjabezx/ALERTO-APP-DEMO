<?php

declare(strict_types=1);

namespace App\Http\Requests\Citizen;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitSimplifiedIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'caller_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:128'],
            'caller_phone' => ['required', 'string', 'min:7', 'max:24', 'regex:/^[0-9+\-\s()]{7,24}$/'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'location_source' => ['nullable', 'in:gps_device,gps_browser,map_picker,manual_pin,admin_entered'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'client_uuid' => ['nullable', 'string', 'max:64'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,heic,mp4,mov,webm', 'max:51200'],
            'media_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'media_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'media_accuracy' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'media_location_source' => ['nullable', 'string', 'max:64'],
            'media_is_live_capture' => ['nullable', 'boolean'],
            'attachment_geo' => ['nullable', 'array', 'max:5'],
            'attachment_geo.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'attachment_geo.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'attachment_geo.*.accuracy' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'attachment_geo.*.location_source' => ['nullable', 'string', 'max:64'],
            'attachment_geo.*.is_live_capture' => ['nullable', 'boolean'],
            'attachment_geo.*.captured_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'latitude.required' => 'Incident GPS coordinates or pinned map location are required.',
            'longitude.required' => 'Incident GPS coordinates or pinned map location are required.',
            'caller_phone.regex' => 'Please enter a valid phone number so DIAL DAPH Command Center can call you back.',
        ];
    }
}
