<?php

declare(strict_types=1);

namespace App\Http\Requests\Personnel;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->isPersonnel();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
        ];
    }
}
