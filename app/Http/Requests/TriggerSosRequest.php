<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TriggerSosRequest extends FormRequest
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
            'sender_name' => ['nullable', 'string', 'max:128'],
            'sender_phone' => ['nullable', 'string', 'max:32'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'location_status' => ['nullable', 'in:live,last_known_stale,unavailable'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
            'current_assignment_id' => ['nullable', 'integer', 'exists:incident_assignments,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'triggered_at' => ['nullable', 'date'],
            'is_offline_queued' => ['nullable', 'boolean'],
        ];
    }
}
