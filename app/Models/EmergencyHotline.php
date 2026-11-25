<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyHotline extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'phone_number',
        'office_id',
        'is_toll_free',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_toll_free' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
