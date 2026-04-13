<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_plate',
        'type',
        'seat_count',
        'payload_kg',
        'status',
        'odometer_km',
        'default_driver_id',
        'inspection_expires_at',
        'insurance_expires_at',
    ];

    protected $casts = [
        'inspection_expires_at' => 'date',
        'insurance_expires_at' => 'date',
    ];

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function complianceDocuments(): HasMany
    {
        return $this->hasMany(VehicleComplianceDocument::class);
    }
}
