<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentPolicy extends Model
{
    protected $fillable = [
        'student_id',
        'route_id',
        'school_year',
        'semester',
        'time_slot',
        'pickup_point_id',
        'dropoff_point_id',
        'effective_from',
        'effective_to',
        'status',
        'deleted_at',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'semester' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'pickup_point_id');
    }

    public function dropoffPoint(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'dropoff_point_id');
    }

    public function policyTripStudents(): HasMany
    {
        return $this->hasMany(PolicyTripStudent::class);
    }
}
