<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

class DispatchRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::deleted(function (DispatchRequest $dispatchRequest): void {
            DatabaseNotification::query()
                ->where('data->dispatch_request_id', $dispatchRequest->getKey())
                ->delete();
        });
    }

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
        'student_count_submitted_at',
        'student_count_submitted_by',
        'notes',
        'service_price',
        'price_filled_by',
        'price_filled_at',
        'assigned_dept_head_id',
        'status',
        'paper_status',
        'paper_received_at',
        'paper_reference',
        'signing_workflow_status',
        'current_signed_version_id',
        'signed_at',
        'signed_by',
        'signature_detected',
        'signature_verified',
        'verification_status',
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
        'signed_at' => 'datetime',
        'signature_detected' => 'boolean',
        'signature_verified' => 'boolean',
        'service_price' => 'decimal:2',
        'price_filled_at' => 'datetime',
        'locked_at' => 'datetime',
        'student_count_submitted_at' => 'datetime',
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

    public function studentCountSubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_count_submitted_by');
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

    public function signedDocumentVersions(): HasMany
    {
        return $this->hasMany(SignedDocumentVersion::class);
    }

    public function currentSignedVersion(): BelongsTo
    {
        return $this->belongsTo(SignedDocumentVersion::class, 'current_signed_version_id');
    }

    public function signedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
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

    /**
     * P2P hoạt động ngoại khóa (CLB), gồm phiếu sinh từ đề xuất định kỳ (snapshot sao chép từ template).
     *
     * @param  Builder<DispatchRequest>  $query
     * @return Builder<DispatchRequest>
     */
    public function scopeExtracurricularOnly(Builder $query): Builder
    {
        return $query->where('trip_type', 'point_to_point')
            ->where(function (Builder $q) {
                $q->where('wizard_snapshot->form->point_purpose_kind', 'extracurricular')
                    ->orWhere('wizard_snapshot->point_purpose_kind', 'extracurricular');
            });
    }

    /**
     * Phiếu CLB định kỳ chưa gửi chốt số HS / điều vận (portal).
     *
     * @param  Builder<DispatchRequest>  $query
     * @return Builder<DispatchRequest>
     */
    public function scopeExtracurricularRecurringDraft(Builder $query): Builder
    {
        return $query
            ->whereNotNull('dispatch_request_template_id')
            ->whereNull('student_count_submitted_at')
            ->extracurricularOnly();
    }

    /**
     * Danh sách điều vận (mng/requests): ẩn bản nháp CLB cho đến khi portal gửi chốt.
     *
     * @param  Builder<DispatchRequest>  $query
     * @return Builder<DispatchRequest>
     */
    public function scopeVisibleOnStaffRequestIndex(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $draft) {
            $draft->extracurricularRecurringDraft();
        });
    }

    /**
     * Portal (danh sách / KPI): ẩn phiếu soft-delete và phiếu hủy đồng bộ lịch (cancelled).
     *
     * @param  Builder<DispatchRequest>  $query
     * @return Builder<DispatchRequest>
     */
    public function scopeVisibleOnPortalRequestIndex(Builder $query): Builder
    {
        return $query
            ->withoutTrashed()
            ->where('status', '!=', 'cancelled');
    }
}
