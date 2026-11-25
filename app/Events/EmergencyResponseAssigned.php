<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\IncidentAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyResponseAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public IncidentAssignment $assignment) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];

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
            'assignment_id' => $this->assignment->id,
            'incident_id' => $this->assignment->incident_id,
            'reference_number' => $this->assignment->incident?->reference_number,
            'office_id' => $this->assignment->office_id,
            'team_id' => $this->assignment->team_id,
            'personnel_id' => $this->assignment->personnel_id,
            'status' => $this->assignment->status,
        ];
    }
}
