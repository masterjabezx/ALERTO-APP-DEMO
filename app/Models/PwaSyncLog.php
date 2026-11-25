<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PwaSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'action_type',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    /**
     * User who initiated the sync.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
