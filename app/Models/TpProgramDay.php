<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TpProgramDay extends Model
{
    public const DAY_OPERATING = 'operating';
    public const DAY_CANCELLED = 'cancelled';
    public const DAY_MAKEUP = 'makeup';

    protected $fillable = [
        'program_id', 'scheduled_date', 'day_type', 'expected_count',
        'driver_id', 'vehicle_id', 'assigned_at', 'assigned_by',
        'estimated_cost', 'cancel_reason', 'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'assigned_at' => 'datetime',
        'expected_count' => 'integer',
        'estimated_cost' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'program_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function execution(): HasOne
    {
        return $this->hasOne(TpTripExecution::class, 'program_day_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(TpDayAbsence::class, 'program_day_id');
    }

    public function scopeOperating(Builder $query): Builder
    {
        return $query->where('day_type', self::DAY_OPERATING);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function effectiveDriver(): ?Driver
    {
        $driverId = $this->driver_id ?? $this->program?->default_driver_id;
        if (! $driverId) {
            return null;
        }
        $driver = $this->relationLoaded('driver') && $this->driver_id
            ? $this->driver
            : Driver::query()->find($driverId);

        if (! $driver || $driver->trashed()) {
            return null;
        }
        if ($driver->employment_status === 'inactive') {
            return null;
        }

        return $driver;
    }

    public function effectiveVehicle(): ?Vehicle
    {
        $vehicleId = $this->vehicle_id ?? $this->program?->default_vehicle_id;
        if (! $vehicleId) {
            return null;
        }

        return $this->relationLoaded('vehicle') && $this->vehicle_id
            ? $this->vehicle
            : Vehicle::query()->find($vehicleId);
    }

    public function effectiveCost(): ?string
    {
        $cost = $this->estimated_cost ?? $this->program?->cost_per_trip;

        return $cost !== null ? (string) $cost : null;
    }
}
