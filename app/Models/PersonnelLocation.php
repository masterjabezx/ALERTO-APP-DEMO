<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelLocation extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'personnel_id',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'accuracy',
        'recorded_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'heading' => 'decimal:2',
            'speed' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'recorded_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }
}
