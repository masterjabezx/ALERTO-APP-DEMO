<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonnelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->canManageOfficeStaffAccounts();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $officeIdRule = ($user && $user->isOffice())
            ? ['nullable', 'exists:offices,id']
            : ['required', 'exists:offices,id'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'office_id' => $officeIdRule,
            'badge_number' => ['required', 'string', 'max:64', 'unique:personnel_profiles,badge_number'],
            'username' => ['nullable', 'string', 'max:64', 'unique:personnel_profiles,username'],
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
