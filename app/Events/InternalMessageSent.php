<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->message->conversation_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        $sender = $this->message->sender;
        $profile = $sender?->personnelProfile;
        $officeUser = $sender?->officeUsers()->with('office')->first();

        $attachments = $this->message->attachments->map(fn ($media) => [
            'id' => $media->id,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'file_size' => $media->file_size_bytes,
            'download_url' => route('messenger.attachments.download', $media->id),
            'latitude' => $media->geoMetadata?->latitude ? (float) $media->geoMetadata->latitude : null,
            'longitude' => $media->geoMetadata?->longitude ? (float) $media->geoMetadata->longitude : null,
            'gps_source' => $media->geoMetadata?->gps_source ?? 'none',
        ]);

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'body' => $this->message->body,
            'message_type' => $this->message->message_type,
            'sender' => [
                'id' => $sender?->id,
                'name' => $sender?->name,
                'account_type' => $sender?->account_type,
                'badge' => $profile?->badge_number,
                'office' => $officeUser?->office?->name ?? ($profile?->office?->name ?? 'LDRRMO Command'),
            ],
            'incident' => $this->message->incident ? [
                'id' => $this->message->incident->id,
                'reference_number' => $this->message->incident->reference_number,
                'category' => $this->message->incident->category?->name,
            ] : null,
            'attachments' => $attachments,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
