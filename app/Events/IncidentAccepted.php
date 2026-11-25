<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.operations')];

        foreach ($this->incident->assignments as $assignment) {
            if ($assignment->office_id) {
                $channels[] = new PrivateChannel("office.{$assignment->office_id}");
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'status' => 'accepted',
            'latitude' => (float) ($this->incident->location?->latitude ?? 0),
            'longitude' => (float) ($this->incident->location?->longitude ?? 0),
        ];
    }
}
