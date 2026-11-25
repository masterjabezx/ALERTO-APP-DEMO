<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonnelSOSCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SosAlert $sosAlert) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];

        if ($this->sosAlert->office_id) {
            $channels[] = new PrivateChannel("office.{$this->sosAlert->office_id}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->sosAlert->id,
            'reference_number' => $this->sosAlert->reference_number,
            'sender_type' => 'personnel',
            'sender_name' => $this->sosAlert->sender_name,
            'sender_phone' => $this->sosAlert->sender_phone,
            'office_id' => $this->sosAlert->office_id,
            'status' => $this->sosAlert->status,
            'location_status' => $this->sosAlert->location_status,
            'latitude' => $this->sosAlert->latitude !== null ? (float) $this->sosAlert->latitude : null,
            'longitude' => $this->sosAlert->longitude !== null ? (float) $this->sosAlert->longitude : null,
            'triggered_at' => $this->sosAlert->triggered_at?->toIso8601String(),
        ];
    }
}
