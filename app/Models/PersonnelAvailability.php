<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAvailability extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'personnel_id',
        'operational_status',
        'status_reason',
        'status_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status_updated_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }

    public function isAvailable(): bool
    {
        return $this->operational_status === 'available';
    }
}
