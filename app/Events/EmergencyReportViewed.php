<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyReportViewed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident, public User $admin) {}

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
            'id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'processed_by_admin_id' => $this->admin->id,
            'processed_by_admin_name' => $this->admin->name,
            'locked_for_admin_at' => $this->incident->locked_for_admin_at?->toIso8601String(),
        ];
    }
}
