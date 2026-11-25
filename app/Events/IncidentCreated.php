<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.operations'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'category' => $this->incident->category?->name,
            'status' => $this->incident->status,
            'priority' => $this->incident->priority,
            'description' => $this->incident->description,
            'barangay' => $this->incident->barangay?->name,
            'latitude' => (float) ($this->incident->location?->latitude ?? 0),
            'longitude' => (float) ($this->incident->location?->longitude ?? 0),
            'submitted_at' => $this->incident->submitted_at?->toIso8601String(),
        ];
    }
}
