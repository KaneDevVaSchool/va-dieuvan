<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TpProgram extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'code', 'name', 'description',
        'origin_name', 'destination_name', 'origin_location_id', 'destination_location_id',
        'departure_time', 'return_time', 'start_date', 'end_date',
        'runs_on', 'excluded_dates', 'extra_dates',
        'default_driver_id', 'default_vehicle_id',
        'cost_per_trip', 'cost_currency', 'cost_notes',
        'responsible_user_id', 'status', 'notes', 'settings', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'runs_on' => 'array',
        'excluded_dates' => 'array',
        'extra_dates' => 'array',
        'settings' => 'array',
        'cost_per_trip' => 'decimal:2',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(TpProgramDay::class, 'program_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TpEnrollment::class, 'program_id');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TpTripExecution::class, 'program_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TpAuditLog::class, 'program_id');
    }

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'default_vehicle_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
}
