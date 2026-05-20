<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PolicyRoute extends Model
{
    protected $fillable = [
        'p2p_policy_term_id',
        'name',
        'origin_campus_id',
        'dest_campus_id',
        'vehicle_id',
        'driver_id',
        'backup_driver_id',
        'morning_start',
        'morning_end',
        'afternoon_start',
        'afternoon_end',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function p2pPolicyTerm(): BelongsTo
    {
        return $this->belongsTo(P2pPolicyTerm::class);
    }

    public function originCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'origin_campus_id');
    }

    public function destCampus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'dest_campus_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function backupDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'backup_driver_id');
    }

    public function policyStudents(): HasMany
    {
        return $this->hasMany(PolicyStudent::class);
    }

    public function tripSlots(): HasMany
    {
        return $this->hasMany(PolicyTripSlot::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
