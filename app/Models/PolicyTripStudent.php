<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyTripStudent extends Model
{
    protected $fillable = [
        'policy_trip_id',
        'student_id',
        'student_policy_id',
        'expected',
        'boarded_at',
        'boarded_by',
        'alighted_at',
        'alighted_by',
        'absence_reason',
        'reported_by',
    ];

    protected $casts = [
        'expected' => 'boolean',
        'boarded_at' => 'datetime',
        'alighted_at' => 'datetime',
    ];

    public function policyTrip(): BelongsTo
    {
        return $this->belongsTo(PolicyTrip::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentPolicy(): BelongsTo
    {
        return $this->belongsTo(StudentPolicy::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
