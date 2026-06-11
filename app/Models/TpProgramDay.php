<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TpProgramDay extends Model
{
    public const DAY_OPERATING = 'operating';

    public const DAY_CANCELLED = 'cancelled';

    public const DAY_MAKEUP = 'makeup';

    protected $fillable = [
        'program_id', 'scheduled_date', 'day_type', 'expected_count',
        'driver_id', 'vehicle_id', 'assigned_at', 'assigned_by',
        'backup_driver_id', 'backup_assigned_at', 'backup_assigned_by',
        'morning_driver_id', 'morning_backup_driver_id',
        'afternoon_driver_id', 'afternoon_backup_driver_id',
        'confirmed_at', 'confirmed_by_driver_id',
        'morning_confirmed_at', 'morning_confirmed_by_driver_id',
        'afternoon_confirmed_at', 'afternoon_confirmed_by_driver_id',
        'estimated_cost', 'cancel_reason', 'notes',
        'attendance_status', 'attendance_confirmed_at', 'attendance_confirmed_by', 'attendance_lock_version',
        'morning_attendance_status', 'morning_attendance_confirmed_at', 'morning_attendance_confirmed_by', 'morning_attendance_lock_version',
        'afternoon_attendance_status', 'afternoon_attendance_confirmed_at', 'afternoon_attendance_confirmed_by', 'afternoon_attendance_lock_version',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'assigned_at' => 'datetime',
        'backup_assigned_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'morning_confirmed_at' => 'datetime',
        'afternoon_confirmed_at' => 'datetime',
        'attendance_confirmed_at' => 'datetime',
        'morning_attendance_confirmed_at' => 'datetime',
        'afternoon_attendance_confirmed_at' => 'datetime',
        'expected_count' => 'integer',
        'attendance_lock_version' => 'integer',
        'morning_attendance_lock_version' => 'integer',
        'afternoon_attendance_lock_version' => 'integer',
        'estimated_cost' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'program_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function backupDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'backup_driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function confirmedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'confirmed_by_driver_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    /**
     * Trả về thời điểm xác nhận cho một ca cụ thể.
     * Nếu không có ca hoặc cột ca chưa được set, fall-back về confirmed_at chung.
     */
    public function slotConfirmedAt(?string $shift): ?\Carbon\Carbon
    {
        if ($shift === 'morning') {
            return $this->morning_confirmed_at ?? $this->confirmed_at;
        }
        if ($shift === 'afternoon') {
            return $this->afternoon_confirmed_at ?? $this->confirmed_at;
        }

        return $this->confirmed_at;
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TpTripExecution::class, 'program_day_id');
    }

    /** @deprecated Ưu tiên executionForShift() khi chương trình có nhiều ca. */
    public function execution(): HasOne
    {
        return $this->hasOne(TpTripExecution::class, 'program_day_id')->latestOfMany();
    }

    public static function normalizeExecutionShift(?string $shift): string
    {
        return in_array($shift, ['morning', 'afternoon'], true) ? $shift : 'morning';
    }

    public function executionForShift(?string $shift): ?TpTripExecution
    {
        $key = self::normalizeExecutionShift($shift);

        if ($this->relationLoaded('executions')) {
            return $this->executions->firstWhere('shift', $key);
        }

        return $this->executions()->where('shift', $key)->first();
    }

    /** Ca khác (cùng ngày) đang chạy — phải hoàn thành trước khi bắt đầu ca mới. */
    public function inProgressShiftBlockingStart(?string $shift): ?string
    {
        $key = self::normalizeExecutionShift($shift);
        $query = $this->executions()->where('status', TpTripExecution::STATUS_IN_PROGRESS);
        if (in_array($shift, ['morning', 'afternoon'], true)) {
            $query->where('shift', '!=', $key);
        }

        $blocking = $query->value('shift');

        return is_string($blocking) ? $blocking : null;
    }

    public function hasInProgressExecutionOtherThan(?string $shift): bool
    {
        return $this->inProgressShiftBlockingStart($shift) !== null;
    }

    public function shiftExecutionStarted(?string $shift): bool
    {
        $exec = $this->executionForShift($shift);
        if (! $exec) {
            return false;
        }

        return in_array($exec->status, [
            TpTripExecution::STATUS_IN_PROGRESS,
            TpTripExecution::STATUS_COMPLETED,
        ], true);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(TpDayAbsence::class, 'program_day_id');
    }

    public function scopeOperating(Builder $query): Builder
    {
        return $query->where('day_type', self::DAY_OPERATING);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('scheduled_date', $date);
    }

    public function effectiveDriver(): ?Driver
    {
        $driverId = $this->driver_id ?? $this->program?->default_driver_id;
        if (! $driverId) {
            return null;
        }
        $driver = $this->relationLoaded('driver') && $this->driver_id
            ? $this->driver
            : Driver::query()->find($driverId);

        if (! $driver || $driver->trashed()) {
            return null;
        }
        if ($driver->employment_status === 'inactive') {
            return null;
        }

        return $driver;
    }

    public function effectiveBackupDriver(): ?Driver
    {
        $driverId = $this->backup_driver_id ?? $this->program?->backup_driver_id;
        if (! $driverId) {
            return null;
        }
        $driver = $this->relationLoaded('backupDriver') && $this->backup_driver_id
            ? $this->backupDriver
            : Driver::query()->find($driverId);

        if (! $driver || $driver->trashed()) {
            return null;
        }
        if ($driver->employment_status === 'inactive') {
            return null;
        }

        return $driver;
    }

    public function effectiveVehicle(): ?Vehicle
    {
        $vehicleId = $this->vehicle_id ?? $this->program?->default_vehicle_id;
        if (! $vehicleId) {
            return null;
        }

        return $this->relationLoaded('vehicle') && $this->vehicle_id
            ? $this->vehicle
            : Vehicle::query()->find($vehicleId);
    }

    public function effectiveCost(): ?string
    {
        $cost = $this->estimated_cost ?? $this->program?->cost_per_trip;

        return $cost !== null ? (string) $cost : null;
    }
}
