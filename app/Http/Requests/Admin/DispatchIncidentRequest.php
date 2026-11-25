<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DispatchIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || ($user->isOffice() && $user->officeUsers()->where('can_dispatch_units', true)->exists()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dispatch_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
