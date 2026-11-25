<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $officeId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:128'],
            'code' => ['required', 'string', 'max:32', Rule::unique('offices', 'code')->ignore($officeId)],
            'department_type' => ['required', 'in:police,fire,medical,disaster,traffic,health,engineering,social_welfare,other'],
            'head_name' => ['nullable', 'string', 'max:128'],
            'head_title' => ['nullable', 'string', 'max:128'],
            'response_responsibilities_summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'contact_phone' => ['required', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:128'],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'service_radius_meters' => ['nullable', 'integer', 'min:500'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],
            'authorized_barangay_ids' => ['nullable', 'array'],
            'authorized_barangay_ids.*' => ['exists:barangays,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
