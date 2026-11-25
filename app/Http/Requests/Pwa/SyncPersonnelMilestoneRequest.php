<?php

declare(strict_types=1);

namespace App\Http\Requests\Pwa;

use Illuminate\Foundation\Http\FormRequest;

class SyncPersonnelMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->account_type === 'personnel' || auth()->user()->account_type === 'admin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'assignment_id' => ['required', 'integer', 'exists:incident_assignments,id'],
            'milestone' => ['required', 'in:acknowledged,on_the_way,on_scene,in_progress,resolved'],
            'expected_version_lock' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
