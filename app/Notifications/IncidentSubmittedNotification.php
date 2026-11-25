<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class IncidentSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public Incident $incident) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'incident_id' => $this->incident->id,
            'reference_number' => $this->incident->reference_number,
            'category' => $this->incident->category?->name ?? 'Emergency',
            'status' => $this->incident->status,
            'message' => 'Your incident report has been received and is queued for LDRRMO intake review.',
            'submitted_at' => $this->incident->submitted_at?->toIso8601String(),
        ];
    }
}
