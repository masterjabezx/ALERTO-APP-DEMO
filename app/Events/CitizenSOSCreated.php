<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CitizenSOSCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SosAlert $sosAlert) {}

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
            'id' => $this->sosAlert->id,
            'reference_number' => $this->sosAlert->reference_number,
            'sender_type' => $this->sosAlert->sender_type,
            'sender_name' => $this->sosAlert->sender_name,
            'sender_phone' => $this->sosAlert->sender_phone,
            'status' => $this->sosAlert->status,
            'location_status' => $this->sosAlert->location_status,
            'latitude' => $this->sosAlert->latitude !== null ? (float) $this->sosAlert->latitude : null,
            'longitude' => $this->sosAlert->longitude !== null ? (float) $this->sosAlert->longitude : null,
            'triggered_at' => $this->sosAlert->triggered_at?->toIso8601String(),
        ];
    }
}
