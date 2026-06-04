<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpTripExecution extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'program_day_id', 'program_id', 'driver_id', 'vehicle_id',
        'driver_snapshot', 'vehicle_snapshot', 'scheduled_time',
        'started_at', 'completed_at', 'cancelled_at', 'cancel_reason',
        'estimated_cost', 'actual_cost', 'cost_notes',
        'cost_confirmed_by', 'cost_confirmed_at',
        'total_expected', 'total_boarded', 'total_alighted', 'total_absent',
        'status', 'device_id', 'sync_version',
    ];

    protected $casts = [
        'driver_snapshot' => 'array',
        'vehicle_snapshot' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cost_confirmed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'total_expected' => 'integer',
        'total_boarded' => 'integer',
        'total_alighted' => 'integer',
        'total_absent' => 'integer',
        'sync_version' => 'integer',
    ];

    public function programDay(): BelongsTo
    {
        return $this->belongsTo(TpProgramDay::class, 'program_day_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'program_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function studentLogs(): HasMany
    {
        return $this->hasMany(TpTripStudentLog::class, 'execution_id');
    }
}
