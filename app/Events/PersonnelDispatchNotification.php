<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonnelDispatchNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident, public int $personnelUserId) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("personnel.{$this->personnelUserId}"),
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'category' => $this->incident->category?->name,
            'priority' => $this->incident->priority,
            'status' => $this->incident->status,
            'latitude' => (float) ($this->incident->location?->latitude ?? 0),
            'longitude' => (float) ($this->incident->location?->longitude ?? 0),
        ];
    }
}
