<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'license_plate',
        'owner_name',
        'frame_engine_number',
        'type',
        'year_manufactured',
        'purchased_at',
        'usage_expires_year',
        'seat_count',
        'payload_kg',
        'insurance_provider',
        'insurance_policy_note',
        'status',
        'odometer_km',
        'default_driver_id',
        'inspection_expires_at',
        'insurance_expires_at',
        'road_fee_expires_at',
        'registration_cycle_note',
        'last_maintenance_at',
        'maintenance_schedule_note',
        'caretaker_name',
        'caretaker_phone',
        'notes',
    ];

    protected $casts = [
        'inspection_expires_at' => 'date',
        'insurance_expires_at' => 'date',
        'purchased_at' => 'date',
        'road_fee_expires_at' => 'date',
        'last_maintenance_at' => 'date',
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

    protected static function booted(): void
    {
        static::deleting(function (Vehicle $vehicle): void {
            if ($vehicle->isForceDeleting()) {
                return;
            }
            Trip::where('vehicle_id', $vehicle->id)->update(['vehicle_id' => null]);
            TpProgram::where('default_vehicle_id', $vehicle->id)->update(['default_vehicle_id' => null]);
        });
    }
}
