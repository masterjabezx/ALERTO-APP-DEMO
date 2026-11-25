<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSosRoutingRuleRequest extends FormRequest
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
            'source_type' => ['required', 'in:all,citizen,personnel'],
            'office_id' => ['required', 'integer', 'exists:offices,id'],
            'team_id' => ['nullable', 'integer', 'exists:response_teams,id'],
            'priority_order' => ['nullable', 'integer', 'min:1', 'max:100'],
            'auto_notify' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
