<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use App\Models\IncidentAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Incident $incident,
        public IncidentAssignment $assignment
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.operations')];

        if ($this->assignment->office_id) {
            $channels[] = new PrivateChannel("office.{$this->assignment->office_id}");
        }

        if ($this->assignment->personnel?->user_id) {
            $channels[] = new PrivateChannel("personnel.{$this->assignment->personnel->user_id}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'incident_id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'assignment_id' => $this->assignment->id,
            'office_id' => $this->assignment->office_id,
            'team_id' => $this->assignment->team_id,
            'personnel_id' => $this->assignment->personnel_id,
            'latitude' => (float) ($this->incident->location?->latitude ?? 0),
            'longitude' => (float) ($this->incident->location?->longitude ?? 0),
        ];
    }
}
