<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',
        'gender',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'verified_citizen',
        'id_type',
        'id_number_hash',
    ];

    protected $hidden = [
        'id_number_hash',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'verified_citizen' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
