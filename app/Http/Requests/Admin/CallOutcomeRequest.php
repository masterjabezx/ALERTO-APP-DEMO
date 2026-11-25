<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CallOutcomeRequest extends FormRequest
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
            'call_outcome' => ['required', 'in:answered,no_answer,busy,invalid_number,callback_required'],
            'phone_number_dialed' => ['nullable', 'string', 'max:32'],
            'verification_notes' => ['nullable', 'string', 'max:2000'],
            'location_confirmed' => ['nullable', 'boolean'],
            'category_confirmed' => ['nullable', 'boolean'],
            'callback_required' => ['nullable', 'boolean'],
            'mark_number_verified' => ['nullable', 'boolean'],
        ];
    }
}
