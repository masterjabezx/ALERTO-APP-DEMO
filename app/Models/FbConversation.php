<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FbConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'fb_user_psid',
        'first_name',
        'last_name',
        'last_interaction_at',
        'unread_count',
        'is_possible_incident',
        'latest_message_text',
    ];

    protected function casts(): array
    {
        return [
            'last_interaction_at' => 'datetime',
            'unread_count' => 'integer',
            'is_possible_incident' => 'boolean',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FbMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(FbMessage::class)->latestOfMany();
    }

    public function intakeRecord(): HasOne
    {
        return $this->hasOne(IncidentIntakeRecord::class);
    }

    public function fullName(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : "Citizen ({$this->fb_user_psid})";
    }
}
