<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SOSAcknowledged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SosAlert $sosAlert) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.operations'),
            new PrivateChannel('super-admin.operations'),
        ];

        if ($this->sosAlert->user_id) {
            $channels[] = new PrivateChannel("App.Models.User.{$this->sosAlert->user_id}");
        }

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
            'status' => $this->sosAlert->status,
            'acknowledged_by' => $this->sosAlert->acknowledged_by,
            'acknowledged_at' => $this->sosAlert->acknowledged_at?->toIso8601String(),
        ];
    }
}
