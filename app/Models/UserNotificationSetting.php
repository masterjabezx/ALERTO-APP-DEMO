<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enable_push',
        'enable_sms',
        'enable_email',
        'enable_sound',
    ];

    protected function casts(): array
    {
        return [
            'enable_push' => 'boolean',
            'enable_sms' => 'boolean',
            'enable_email' => 'boolean',
            'enable_sound' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
