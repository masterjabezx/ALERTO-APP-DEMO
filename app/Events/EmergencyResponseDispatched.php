<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyResponseDispatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];

        foreach ($this->incident->assignments as $assignment) {
            if ($assignment->office_id) {
                $channels[] = new PrivateChannel("office.{$assignment->office_id}");
            }
            if ($assignment->personnel?->user_id) {
                $channels[] = new PrivateChannel("personnel.{$assignment->personnel->user_id}");
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'status' => $this->incident->status,
            'workflow_stage' => $this->incident->workflow_stage,
            'dispatched_at' => $this->incident->dispatched_at?->toIso8601String(),
        ];
    }
}
