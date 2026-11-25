<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class VerifyContactRequest extends FormRequest
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
            'contact_number' => ['required', 'string', 'max:32'],
            'verification_method' => ['required', 'in:callback,otp_sms'],
            'status' => ['required', 'in:unverified,verification_pending,verified,failed_verification'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
