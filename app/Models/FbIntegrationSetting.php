<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'page_name',
        'app_id',
        'verify_token',
        'is_active',
        'webhook_verified',
        'last_webhook_at',
        'last_message_at',
        'last_error',
    ];

    protected $hidden = [
        'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'webhook_verified' => 'boolean',
            'last_webhook_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }
}
