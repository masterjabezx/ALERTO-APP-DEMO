<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConvertFbIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:incident_categories,id'],
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'phone_number' => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
            'priority' => ['required', 'string', 'in:critical,high,medium,low'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a valid incident category.',
            'barangay_id.required' => 'Please select the incident barangay.',
            'phone_number.regex' => 'The phone number must be a valid 11-digit Philippine mobile number starting with 09.',
            'description.required' => 'An incident description is required.',
        ];
    }
}
