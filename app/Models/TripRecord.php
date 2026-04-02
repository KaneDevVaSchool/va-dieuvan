<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'created_by',
        'start_odometer_km',
        'end_odometer_km',
        'distance_km',
        'driver_notes',
        'dispatcher_notes',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

