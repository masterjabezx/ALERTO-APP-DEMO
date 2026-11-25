<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'user_id',
        'office_level',
        'can_review_incidents',
        'can_dispatch_units',
        'can_manage_accounts',
    ];

    protected function casts(): array
    {
        return [
            'can_review_incidents' => 'boolean',
            'can_dispatch_units' => 'boolean',
            'can_manage_accounts' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
