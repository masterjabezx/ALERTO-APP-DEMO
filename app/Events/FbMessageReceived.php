<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\FbMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FbMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public FbMessage $message) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.facebook-messenger'),
        ];
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;
        $intake = $conversation?->intakeRecord;

        return [
            'message_id' => $this->message->id,
            'mid' => $this->message->fb_message_mid,
            'conversation_id' => $this->message->fb_conversation_id,
            'direction' => $this->message->direction,
            'text' => $this->message->message_text,
            'sent_at' => $this->message->sent_at?->toIso8601String(),
            'sender' => [
                'psid' => $conversation?->fb_user_psid,
                'name' => $conversation?->fullName(),
            ],
            'unread_count' => $conversation?->unread_count ?? 0,
            'is_possible_incident' => $conversation?->is_possible_incident ?? false,
            'intake' => $intake ? [
                'id' => $intake->id,
                'status' => $intake->status,
                'current_step' => $intake->current_step,
                'is_complete' => $intake->is_complete,
            ] : null,
        ];
    }
}
