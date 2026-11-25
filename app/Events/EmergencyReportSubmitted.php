<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyReportSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'caller_name' => $this->incident->caller_name,
            'caller_phone' => $this->incident->caller_phone,
            'is_guest_report' => (bool) $this->incident->is_guest_report,
            'workflow_stage' => $this->incident->workflow_stage,
            'status' => $this->incident->status,
            'barangay' => $this->incident->barangay?->name,
            'latitude' => (float) ($this->incident->location?->latitude ?? 0),
            'longitude' => (float) ($this->incident->location?->longitude ?? 0),
            'accuracy' => $this->incident->location?->accuracy !== null ? (float) $this->incident->location->accuracy : null,
            'submitted_at' => $this->incident->submitted_at?->toIso8601String(),
        ];
    }
}
