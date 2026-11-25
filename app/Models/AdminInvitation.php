<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminInvitation extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'invited_by',
        'token_hash',
        'permissions',
        'expires_at',
        'accepted_at',
        'created_user_id',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function isValid(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }
}
