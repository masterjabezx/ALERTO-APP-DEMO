<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeUserRequest extends FormRequest
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
        $userId = $this->route('userId') ?? $this->input('user_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['required', 'string', 'max:32', Rule::unique('users', 'phone_number')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'office_level' => ['required', 'in:office_admin,office_staff'],
            'can_review_incidents' => ['nullable', 'boolean'],
            'can_dispatch_units' => ['nullable', 'boolean'],
            'can_manage_accounts' => ['nullable', 'boolean'],
        ];
    }
}
