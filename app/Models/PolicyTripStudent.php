<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một học sinh trong một chuyến policy — trạng thái điểm danh lên/xuống xe
 * và lý do vắng (§3.3). `boarded_by` / `alighted_by` lưu audit ai thao tác (U1).
 *
 * @see docs/p2p.md §3.3
 */
class PolicyTripStudent extends Model
{
    /** Vắng có phép — phụ huynh/GVCN báo trước qua điều vận (§7.1 Kênh 1). */
    public const ABSENCE_REPORTED = 'absent_reported';
    /** Vắng không phép — tài xế phát hiện tại chỗ (§7.1 Kênh 2). */
    public const ABSENCE_NO_NOTICE = 'absent_no_notice';
    /** Hủy muộn — báo trong ngưỡng cancel_threshold (§7.1 Kênh 3). */
    public const ABSENCE_LATE_CANCELLATION = 'late_cancellation';

    public const ABSENCE_REASONS = [
        self::ABSENCE_REPORTED,
        self::ABSENCE_NO_NOTICE,
        self::ABSENCE_LATE_CANCELLATION,
    ];

    /** Lý do vắng mà tài xế được phép tự đánh trên app (§10.2). */
    public const ABSENCE_REASONS_DRIVER = [
        self::ABSENCE_NO_NOTICE,
        self::ABSENCE_LATE_CANCELLATION,
    ];

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

    public function isAbsent(): bool
    {
        return $this->absence_reason !== null;
    }

    /** HS đã được xử lý (lên xe hoặc đánh vắng) — điều kiện để complete (§5.2). */
    public function isHandled(): bool
    {
        return $this->boarded_at !== null || $this->absence_reason !== null;
    }
}
