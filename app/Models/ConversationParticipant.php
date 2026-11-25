<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'is_admin',
        'last_read_message_id',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
