<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssessAndDeployRequest extends FormRequest
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
        return [
            'category_id' => ['required', 'exists:incident_categories,id'],
            'priority' => ['required', 'in:critical,high,medium,low'],
            'incident_description' => ['required', 'string', 'min:5', 'max:5000'],
            'verified_barangay_id' => ['nullable', 'exists:barangays,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'people_involved_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'injured_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'needing_rescue_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'fatalities_count' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'casualty_counts_unknown' => ['nullable', 'boolean'],
            'injury_medical_needs' => ['nullable', 'string', 'max:2000'],
            'hazards_present' => ['nullable', 'string', 'max:2000'],
            'assistance_required' => ['nullable', 'string', 'max:2000'],
            'required_equipment' => ['nullable', 'string', 'max:2000'],
            'required_office_ids' => ['nullable', 'array'],
            'required_office_ids.*' => ['integer', 'exists:offices,id'],
            'required_team_ids' => ['nullable', 'array'],
            'required_team_ids.*' => ['integer', 'exists:response_teams,id'],
            'required_qualifications' => ['nullable', 'array'],
            'required_qualifications.*' => ['string', 'max:128'],
            'personnel_ids' => ['nullable', 'array'],
            'personnel_ids.*' => ['integer', 'exists:personnel_profiles,id'],
            'landmark_details' => ['nullable', 'string', 'max:255'],
            'additional_remarks' => ['nullable', 'string', 'max:2000'],
            'location_confirmed' => ['nullable', 'boolean'],
            'category_confirmed' => ['nullable', 'boolean'],
            'mark_number_verified' => ['nullable', 'boolean'],
            'assign_office_only' => ['nullable', 'boolean'],
            'manual_override_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
