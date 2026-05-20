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
        'passenger_check_ins',
        'supplement_transports',
        'schedule_assignments',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'passenger_check_ins' => 'array',
        'supplement_transports' => 'array',
        'schedule_assignments' => 'array',
        'depart_at' => 'datetime',
        'arrive_by' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function isPaid(): bool
    {
        return ($this->payment_status ?? 'unpaid') === 'paid';
    }

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

    public function tripPassengers(): HasMany
    {
        return $this->hasMany(TripPassenger::class)->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TripEvent::class);
    }
}
