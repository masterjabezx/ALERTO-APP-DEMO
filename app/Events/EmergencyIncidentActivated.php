<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyIncidentActivated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];

        if ($this->incident->reporter_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->incident->reporter_id}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'category' => $this->incident->category?->name,
            'priority' => $this->incident->priority,
            'status' => $this->incident->status,
            'workflow_stage' => $this->incident->workflow_stage,
            'has_assignment_warning' => (bool) $this->incident->has_assignment_warning,
            'assignment_warning_message' => $this->incident->assignment_warning_message,
        ];
    }
}
