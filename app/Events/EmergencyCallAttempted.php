<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EmergencyCallAttempt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyCallAttempted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EmergencyCallAttempt $attempt) {}

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
            'id' => $this->attempt->id,
            'incident_id' => $this->attempt->incident_id,
            'reference_number' => $this->attempt->incident?->reference_number,
            'initiated_by' => $this->attempt->initiated_by,
            'phone_number_dialed' => $this->attempt->phone_number_dialed,
            'call_outcome' => $this->attempt->call_outcome,
            'initiated_at' => $this->attempt->initiated_at?->toIso8601String(),
        ];
    }
}
