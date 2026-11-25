<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'incident_id',
        'title',
        'created_by',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['is_admin', 'last_read_message_id', 'joined_at']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        if (! $participant) {
            return 0;
        }

        $lastReadId = $participant->last_read_message_id ?? 0;

        return $this->messages()->where('id', '>', $lastReadId)->where('sender_id', '!=', $userId)->count();
    }
}
