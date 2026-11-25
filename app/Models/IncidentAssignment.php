<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentAssignment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'incident_id',
        'office_id',
        'team_id',
        'personnel_id',
        'assigned_by',
        'assignment_type',
        'status',
        'assigned_at',
        'acknowledged_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(ResponseTeam::class, 'team_id');
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(PersonnelProfile::class, 'personnel_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function responseLogs(): HasMany
    {
        return $this->hasMany(ResponseLog::class, 'assignment_id');
    }
}
