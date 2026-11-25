<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentAcknowledged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Incident $incident,
        public int $personnelId
    ) {}

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
            'status' => 'acknowledged',
            'personnel_id' => $this->personnelId,
            'acknowledged_at' => now()->toIso8601String(),
        ];
    }
}
