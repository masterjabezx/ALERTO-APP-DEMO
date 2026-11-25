<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseTeamRequest extends FormRequest
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
            'office_id' => ['required', 'exists:offices,id'],
            'team_name' => ['required', 'string', 'max:128'],
            'call_sign' => ['required', 'string', 'max:64', 'unique:response_teams,call_sign'],
            'team_type' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'response_base_id' => ['nullable', 'exists:response_bases,id'],
            'team_leader_id' => ['nullable', 'exists:personnel_profiles,id'],
            'authorized_categories' => ['nullable', 'array'],
        ];
    }
}
