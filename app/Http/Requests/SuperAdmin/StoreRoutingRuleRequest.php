<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoutingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:incident_categories,id'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'priority' => ['nullable', 'in:critical,high,medium,low'],
            'target_office_id' => ['required', 'integer', 'exists:offices,id'],
            'fallback_office_id' => ['nullable', 'integer', 'exists:offices,id'],
            'support_office_ids' => ['nullable', 'array'],
            'support_office_ids.*' => ['integer', 'exists:offices,id'],
            'response_team_id' => ['nullable', 'integer', 'exists:response_teams,id'],
            'required_qualifications' => ['nullable', 'array'],
            'required_qualifications.*' => ['string', 'max:128'],
            'required_responders_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'escalation_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'escalation_notes' => ['nullable', 'string', 'max:1000'],
            'auto_dispatch' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
