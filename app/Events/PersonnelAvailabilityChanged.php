<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PersonnelProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonnelAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PersonnelProfile $personnel,
        public string $operationalStatus,
        public string $presenceStatus
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.operations')];

        $officeId = $this->personnel->teamMemberships()->with('team')->first()?->team?->office_id;
        if ($officeId) {
            $channels[] = new PrivateChannel("office.{$officeId}");
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'personnel_id' => $this->personnel->id,
            'name' => $this->personnel->user?->name,
            'operational_status' => $this->operationalStatus,
            'presence_status' => $this->presenceStatus,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
