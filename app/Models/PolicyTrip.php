<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyTrip extends Model
{
    protected $fillable = [
        'trip_date',
        'time_slot',
        'route_id',
        'driver_id',
        'vehicle_id',
        'status',
        'planned_departure',
        'actual_departure',
        'actual_arrival',
        'expected_count',
        'boarded_count',
        'absent_count',
        'route_snapshot',
        'generated_at',
        'generated_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'generated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'route_snapshot' => 'array',
        'expected_count' => 'integer',
        'boarded_count' => 'integer',
        'absent_count' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(PolicyTripStudent::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PolicyTripAudit::class);
    }
}
