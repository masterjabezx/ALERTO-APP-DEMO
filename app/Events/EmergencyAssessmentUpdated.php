<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EmergencyAssessment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyAssessmentUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EmergencyAssessment $assessment) {}

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
            'id' => $this->assessment->id,
            'incident_id' => $this->assessment->incident_id,
            'reference_number' => $this->assessment->incident?->reference_number,
            'category' => $this->assessment->category?->name,
            'priority' => $this->assessment->priority,
            'verified_barangay' => $this->assessment->verifiedBarangay?->name,
            'confirmed_at' => $this->assessment->confirmed_at?->toIso8601String(),
        ];
    }
}
