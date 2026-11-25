<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isOffice());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:accepted,rejected'],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'max:255'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'A rejection reason is strictly required when rejecting an emergency report.',
        ];
    }
}
