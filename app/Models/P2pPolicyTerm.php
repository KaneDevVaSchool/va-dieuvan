<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class P2pPolicyTerm extends Model
{
    protected $fillable = [
        'academic_term_id',
        'operating_from',
        'operating_to',
        'default_morning_start',
        'default_morning_end',
        'default_afternoon_start',
        'default_afternoon_end',
        'weekdays_mask',
        'exclude_fixed_holidays',
        'status',
        'activated_at',
        'activated_by',
        'generation_run_id',
    ];

    protected $casts = [
        'operating_from' => 'date',
        'operating_to' => 'date',
        'activated_at' => 'datetime',
        'weekdays_mask' => 'integer',
        'exclude_fixed_holidays' => 'boolean',
    ];

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(PolicyTermHoliday::class);
    }

    public function skipDates(): HasMany
    {
        return $this->hasMany(PolicyTermSkipDate::class);
    }

    public function policyRoutes(): HasMany
    {
        return $this->hasMany(PolicyRoute::class);
    }

    public function tripSlots(): HasMany
    {
        return $this->hasMany(PolicyTripSlot::class);
    }

    public function generationRuns(): HasMany
    {
        return $this->hasMany(PolicyGenerationRun::class);
    }

    public function activeGenerationRun(): BelongsTo
    {
        return $this->belongsTo(PolicyGenerationRun::class, 'generation_run_id');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }
}
