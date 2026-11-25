<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfficeUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:32', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8'],
            'office_level' => ['required', 'in:office_admin,office_staff'],
            'can_review_incidents' => ['nullable', 'boolean'],
            'can_dispatch_units' => ['nullable', 'boolean'],
            'can_manage_accounts' => ['nullable', 'boolean'],
        ];
    }
}
