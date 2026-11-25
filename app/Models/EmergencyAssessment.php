<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyAssessment extends Model
{
    protected $fillable = [
        'incident_id',
        'assessed_by',
        'category_id',
        'priority',
        'verified_barangay_id',
        'incident_description',
        'people_involved_count',
        'injured_count',
        'needing_rescue_count',
        'fatalities_count',
        'casualty_counts_unknown',
        'injury_medical_needs',
        'hazards_present',
        'assistance_required',
        'required_equipment',
        'required_office_ids',
        'required_team_ids',
        'required_qualifications',
        'landmark_details',
        'additional_remarks',
        'location_confirmed',
        'category_confirmed',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'people_involved_count' => 'integer',
            'injured_count' => 'integer',
            'needing_rescue_count' => 'integer',
            'fatalities_count' => 'integer',
            'casualty_counts_unknown' => 'boolean',
            'required_office_ids' => 'array',
            'required_team_ids' => 'array',
            'required_qualifications' => 'array',
            'location_confirmed' => 'boolean',
            'category_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncidentCategory::class, 'category_id');
    }

    public function verifiedBarangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class, 'verified_barangay_id');
    }
}
