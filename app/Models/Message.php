<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'incident_id',
        'reply_to_message_id',
        'body',
        'message_type',
        'edited_at',
    ];

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_message_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(MediaAttachment::class, 'message_attachments', 'message_id', 'media_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(MessageReadReceipt::class);
    }

    public function isReadBy(int $userId): bool
    {
        return $this->readReceipts()->where('user_id', $userId)->exists();
    }
}
