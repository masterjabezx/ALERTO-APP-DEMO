<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\PersonnelProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonnelLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PersonnelProfile $personnel,
        public float $latitude,
        public float $longitude,
        public ?float $heading = null,
        public ?float $speed = null,
        public ?float $accuracy = null
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
            'user_id' => $this->personnel->user_id,
            'name' => $this->personnel->user?->name,
            'badge_number' => $this->personnel->badge_number,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'heading' => $this->heading,
            'speed' => $this->speed,
            'accuracy' => $this->accuracy,
            'recorded_at' => now()->toIso8601String(),
        ];
    }
}
