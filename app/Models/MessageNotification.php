<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageNotification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'user_id',
        'is_delivered',
        'delivered_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_delivered' => 'boolean',
            'delivered_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
