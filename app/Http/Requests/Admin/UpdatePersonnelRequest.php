<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonnelRequest extends FormRequest
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
        $personnelId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'password' => ['nullable', 'string', 'min:8'],
            'office_id' => ['required', 'exists:offices,id'],
            'badge_number' => ['required', 'string', 'max:64'],
            'username' => ['nullable', 'string', 'max:64'],
            'rank_designation' => ['required', 'string', 'max:128'],
            'specialization' => ['nullable', 'string', 'max:128'],
            'is_team_leader' => ['nullable', 'boolean'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['exists:response_teams,id'],
            'barangay_ids' => ['nullable', 'array'],
            'barangay_ids.*' => ['exists:barangays,id'],
            'primary_barangay_id' => ['nullable', 'exists:barangays,id'],
            'authorized_categories' => ['nullable', 'array'],
        ];
    }
}
