<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || ($user->isOffice() && $user->officeUsers()->where('can_dispatch_units', true)->exists()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'office_id' => ['required', 'exists:offices,id'],
            'team_id' => ['nullable', 'exists:response_teams,id'],
            'personnel_id' => ['nullable', 'exists:personnel_profiles,id'],
            'personnel_ids' => ['nullable', 'array'],
            'personnel_ids.*' => ['exists:personnel_profiles,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'assignment_type' => ['nullable', 'in:manual,automatic_rule'],
        ];
    }
}
