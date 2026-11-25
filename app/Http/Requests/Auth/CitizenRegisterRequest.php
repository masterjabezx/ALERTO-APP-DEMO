<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CitizenRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:128'],
            'middle_name' => ['nullable', 'string', 'max:128'],
            'last_name' => ['required', 'string', 'max:128'],
            'suffix' => ['nullable', 'string', 'max:32'],
            'phone_number' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone_number'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'barangay_id' => ['required', 'exists:barangays,id'],
            'street_address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'blood_type' => ['nullable', 'string', 'max:8'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'The mobile number must be a valid 11-digit Philippine mobile format (e.g. 09171234567).',
            'barangay_id.required' => 'Please select your resident barangay.',
            'street_address.required' => 'Street / House No. / Purok address is required.',
            'latitude.required' => 'Mandatory residential location pin is required.',
            'longitude.required' => 'Mandatory residential location pin is required.',
        ];
    }
}
