<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class DispatchRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $hidden = [
        'wizard_snapshot',
    ];

    protected $fillable = [
        'requester_id',
        'dispatch_request_template_id',
        'cloned_from_id',
        'approved_by',
        'source_channel',
        'is_urgent',
        'trip_type',
        'origin',
        'destination',
        'depart_at',
        'arrive_by',
        'passenger_count',
        'student_count_actual',
        'locked_at',
        'notes',
        'service_price',
        'price_filled_by',
        'price_filled_at',
        'assigned_dept_head_id',
        'status',
        'paper_status',
        'paper_received_at',
        'paper_reference',
        'rejection_reason',
        'wizard_snapshot',
        'urgent_reason',
        'urgent_trigger',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_by' => 'datetime',
        'is_urgent' => 'boolean',
        'paper_received_at' => 'datetime',
        'service_price' => 'decimal:2',
        'price_filled_at' => 'datetime',
        'locked_at' => 'datetime',
        'wizard_snapshot' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function dispatchRequestTemplate(): BelongsTo
    {
        return $this->belongsTo(DispatchRequestTemplate::class, 'dispatch_request_template_id');
    }

    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'cloned_from_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function priceFiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'price_filled_by');
    }

    public function assignedDeptHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_dept_head_id');
    }

    public function trip(): HasOne
    {
        return $this->hasOne(Trip::class);
    }

    public function cargoShipment(): HasOne
    {
        return $this->hasOne(CargoShipment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public static function wouldBeAutoUrgent(string $tripType, Carbon $departAt): bool
    {
        $threshold = DispatchSetting::urgentThresholdHoursForTripType($tripType);
        $hoursUntil = $departAt->diffInMinutes(Carbon::now(), true) / 60;

        return $hoursUntil >= 0 && $hoursUntil <= $threshold;
    }

    /** @return array{0: bool, 1: 'auto'|'manual'|null} */
    public static function resolveUrgentTrigger(string $tripType, Carbon $departAt, bool $clientWantsUrgent): array
    {
        if (static::wouldBeAutoUrgent($tripType, $departAt)) {
            return [true, 'auto'];
        }
        if ($clientWantsUrgent) {
            return [true, 'manual'];
        }

        return [false, null];
    }

    public function isUrgentAuto(): bool
    {
        return $this->urgent_trigger === 'auto';
    }
}
