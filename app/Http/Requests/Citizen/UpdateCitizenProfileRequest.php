<?php

declare(strict_types=1);

namespace App\Http\Requests\Citizen;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCitizenProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCitizen() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'min:7',
                'max:24',
                'regex:/^[0-9+\-\s()]{7,24}$/',
                Rule::unique('users', 'phone_number')->ignore($userId),
            ],
        ];
    }
}
