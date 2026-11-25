<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_priority',
        'icon',
        'color_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'category_id');
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(IncidentRoutingRule::class, 'category_id');
    }
}
