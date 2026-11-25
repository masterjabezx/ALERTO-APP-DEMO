<?php

declare(strict_types=1);

namespace App\Http\Requests\Office;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OfficeReviewIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isOffice()) {
            return false;
        }

        // Office Admin with can_review_incidents
        $officeUser = $user->officeUsers()->first();

        return (bool) ($officeUser && $officeUser->can_review_incidents);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['nullable', 'in:accept,reject'],
            'decision' => ['nullable', 'in:accepted,rejected,accept,reject'],
            'review_remarks' => ['nullable', 'string', 'max:1000'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'rejection_category' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $decision = $this->input('decision') ?? $this->input('action');
            if (! $decision) {
                $validator->errors()->add('action', 'The action or decision field is required.');

                return;
            }

            $isReject = in_array(strtolower((string) $decision), ['reject', 'rejected'], true);
            if ($isReject && empty(trim((string) $this->input('rejection_reason')))) {
                $validator->errors()->add('rejection_reason', 'A specific, documented rejection reason is mandatory when declining an emergency incident.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'A specific, documented rejection reason is mandatory when declining an emergency incident.',
            'rejection_category.required_if' => 'Please select an authorized rejection category.',
        ];
    }
}
