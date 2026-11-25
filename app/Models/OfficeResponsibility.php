<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeResponsibility extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'category_slug',
        'notes',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
