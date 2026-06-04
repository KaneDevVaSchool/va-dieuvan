<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chuyến đưa đón học sinh chính sách (P2P). Sinh tự động theo lịch ngày học
 * hoặc tạo thủ công; điều vận gán tài xế, tài xế điểm danh lên/xuống xe.
 *
 * @see docs/p2p.md §3.2
 */
class PolicyTrip extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_ASSIGNED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** Trạng thái chưa khởi hành — còn được phép gán/hủy/đồng bộ HS. */
    public const STATUSES_PENDING = [self::STATUS_SCHEDULED, self::STATUS_ASSIGNED];

    public const TIME_SLOT_MORNING = 'morning';
    public const TIME_SLOT_AFTERNOON = 'afternoon';
    public const TIME_SLOTS = [self::TIME_SLOT_MORNING, self::TIME_SLOT_AFTERNOON];

    protected $fillable = [
        'trip_date',
        'time_slot',
        'route_id',
        'driver_id',
        'vehicle_id',
        'status',
        'planned_departure',
        'actual_departure',
        'actual_arrival',
        'expected_count',
        'boarded_count',
        'absent_count',
        'route_snapshot',
        'generated_at',
        'generated_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'notes',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'actual_departure' => 'datetime',
        'actual_arrival' => 'datetime',
        'generated_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'route_snapshot' => 'array',
        'expected_count' => 'integer',
        'boarded_count' => 'integer',
        'absent_count' => 'integer',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(PolicyTripStudent::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(PolicyTripAudit::class);
    }

    public function isPending(): bool
    {
        return in_array($this->status, self::STATUSES_PENDING, true);
    }

    /** Chuyến trong một ngày cụ thể. */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('trip_date', $date);
    }

    /** Chuyến từ hôm nay trở đi, chưa khởi hành (dùng cho đồng bộ policy §4.4). */
    public function scopeUpcomingPending(Builder $query): Builder
    {
        return $query
            ->whereIn('status', self::STATUSES_PENDING)
            ->whereDate('trip_date', '>=', now()->toDateString());
    }
}
