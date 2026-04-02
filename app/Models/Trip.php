<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'dispatch_request_id',
        'dispatcher_id',
        'vehicle_id',
        'driver_id',
        'transport_provider_id',
        'external_vehicle_ref',
        'external_driver_ref',
        'status',
        'depart_at',
        'arrive_by',
        'started_at',
        'completed_at',
        'lock_version',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_by' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(DispatchRequest::class);
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function transportProvider(): BelongsTo
    {
        return $this->belongsTo(TransportProvider::class);
    }

    public function record(): HasOne
    {
        return $this->hasOne(TripRecord::class);
    }

    public function costs(): HasMany
    {
        return $this->hasMany(TripCost::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TripEvent::class);
    }
}

