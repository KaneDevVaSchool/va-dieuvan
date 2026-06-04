<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chính sách đưa đón của một học sinh trên một tuyến + ca + học kỳ.
 * Soft-delete bằng `deleted_at` thủ công để giữ toàn vẹn FK lịch sử (§3.1, U3).
 *
 * @see docs/p2p.md §3.1
 */
class StudentPolicy extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUSES = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_SUSPENDED];

    /** Trạng thái khiến HS bị loại khỏi chuyến tương lai (§4.4). */
    public const STATUSES_STOP_SERVICE = [self::STATUS_INACTIVE, self::STATUS_SUSPENDED];

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function policyTripStudents(): HasMany
    {
        return $this->hasMany(PolicyTripStudent::class);
    }

    /** Bỏ qua bản ghi đã soft-delete. */
    public function scopeNotDeleted(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Chính sách đang phục vụ trong ngày `$date` cho một ca + học kỳ (§4.3).
     */
    public function scopeServingOn(Builder $query, string $date, string $timeSlot, int $semester): Builder
    {
        return $query
            ->whereNull('deleted_at')
            ->where('status', self::STATUS_ACTIVE)
            ->where('time_slot', $timeSlot)
            ->where('semester', $semester)
            ->whereDate('effective_from', '<=', $date)
            ->whereDate('effective_to', '>=', $date);
    }
}
