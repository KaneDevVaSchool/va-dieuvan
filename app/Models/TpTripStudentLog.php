<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpTripStudentLog extends Model
{
    public const FINAL_PENDING = 'pending';
    public const FINAL_BOARDED = 'boarded';
    public const FINAL_ALIGHTED = 'alighted';
    public const FINAL_ABSENT = 'absent';

    protected $fillable = [
        'execution_id', 'student_id', 'student_snapshot',
        'initial_status', 'boarded_at', 'boarded_by',
        'alighted_at', 'alighted_by', 'absent_at', 'absent_by',
        'absence_type', 'absence_notes', 'driver_notes', 'final_status',
        'client_timestamp', 'sync_status',
    ];

    protected $casts = [
        'student_snapshot' => 'array',
        'boarded_at' => 'datetime',
        'alighted_at' => 'datetime',
        'absent_at' => 'datetime',
        'client_timestamp' => 'datetime',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(TpTripExecution::class, 'execution_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpStudent::class, 'student_id');
    }
}
