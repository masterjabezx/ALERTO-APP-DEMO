<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fb_message_id',
        'attachment_type',
        'url',
        'payload',
        'saved_media_id',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(FbMessage::class, 'fb_message_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAttachment::class, 'saved_media_id');
    }
}
