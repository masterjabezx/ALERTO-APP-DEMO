<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FbWebhookEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'processed',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'processed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
