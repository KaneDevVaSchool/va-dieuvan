<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DispatchRequestTemplate extends Model
{
    protected $fillable = [
        'requester_id',
        'dispatch_package_id',
        'is_active',
        'trip_type',
        'origin',
        'destination',
        'passenger_count',
        'notes',
        'arrive_offset_minutes',
        'recurrence_rule',
        'recurrence_end_date',
        'repeat_count',
        'recurrence_time',
        'start_date',
        'return_time',
        'wizard_snapshot',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'recurrence_end_date' => 'date',
        'start_date' => 'date',
        'recurrence_rule' => 'array',
        'wizard_snapshot' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function dispatchPackage(): BelongsTo
    {
        return $this->belongsTo(DispatchPackage::class);
    }

    public function dispatchRequests(): HasMany
    {
        return $this->hasMany(DispatchRequest::class, 'dispatch_request_template_id');
    }

    /** Ngày kết thúc chuỗi (inclusive), theo TZ app. */
    public function effectiveRecurrenceEndDay(?string $timezone = null): ?Carbon
    {
        $timezone = $timezone ?: (config('app.timezone') ?: 'UTC');

        if ($this->recurrence_end_date !== null) {
            return Carbon::parse((string) $this->recurrence_end_date, $timezone)->endOfDay();
        }

        if ($this->repeat_count !== null && $this->start_date !== null) {
            $weeks = max(1, (int) $this->repeat_count);

            return Carbon::parse((string) $this->start_date, $timezone)
                ->startOfDay()
                ->addWeeks($weeks)
                ->subDay()
                ->endOfDay();
        }

        return null;
    }
}
