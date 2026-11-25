<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\IncidentIntakeRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentIntakeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public IncidentIntakeRecord $intake) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.facebook-messenger'),
            new PrivateChannel('admin.operations'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'intake_id' => $this->intake->id,
            'conversation_id' => $this->intake->fb_conversation_id,
            'status' => $this->intake->status,
            'current_step' => $this->intake->current_step,
            'is_complete' => $this->intake->is_complete,
            'category' => $this->intake->category?->name,
            'barangay' => $this->intake->barangay?->name,
            'phone' => $this->intake->collected_phone,
            'generated_incident' => $this->intake->incident ? [
                'id' => $this->intake->incident->id,
                'reference_number' => $this->intake->incident->reference_number,
                'status' => $this->intake->incident->status,
            ] : null,
            'updated_at' => $this->intake->updated_at?->toIso8601String(),
        ];
    }
}
