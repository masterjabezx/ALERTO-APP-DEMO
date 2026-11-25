<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseBaseRequest extends FormRequest
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
            'barangay_id' => ['required', 'exists:barangays,id'],
            'base_name' => ['required', 'string', 'max:128'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
        ];
    }
}
