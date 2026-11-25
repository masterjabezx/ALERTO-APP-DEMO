<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FbMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'fb_conversation_id',
        'fb_message_mid',
        'direction',
        'message_text',
        'raw_payload',
        'delivery_status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(FbConversation::class, 'fb_conversation_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FbAttachment::class, 'fb_message_id');
    }
}
