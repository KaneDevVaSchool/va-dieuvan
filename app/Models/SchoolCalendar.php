<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lịch học/nghỉ theo ngày của trường. Job sinh chuyến tra `day_type` + `semester`
 * trực tiếp từ đây — không tính ngầm định (§3.4, CRITICAL FIX L1).
 *
 * @see docs/p2p.md §3.4
 */
class SchoolCalendar extends Model
{
    public const DAY_SCHOOL = 'school_day';
    public const DAY_HOLIDAY = 'holiday';
    public const DAY_WEEKEND = 'weekend';
    public const DAY_MAKEUP = 'makeup_day';

    public const DAY_TYPES = [
        self::DAY_SCHOOL,
        self::DAY_HOLIDAY,
        self::DAY_WEEKEND,
        self::DAY_MAKEUP,
    ];

    /** Ngày đủ điều kiện sinh chuyến (§4.1). */
    public const DAY_TYPES_SERVICED = [self::DAY_SCHOOL, self::DAY_MAKEUP];

    protected $fillable = [
        'school_year',
        'semester',
        'date',
        'day_type',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'semester' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Ngày học/bù — đủ điều kiện chạy chuyến. */
    public function isServiceDay(): bool
    {
        return in_array($this->day_type, self::DAY_TYPES_SERVICED, true);
    }

    public function scopeServiceDays(Builder $query): Builder
    {
        return $query->whereIn('day_type', self::DAY_TYPES_SERVICED);
    }
}
