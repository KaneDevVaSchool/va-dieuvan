<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

class VehicleMaintenanceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'driver_id',
        'type',
        'name',
        'expiry_date',
        'next_service_date',
        'last_service_date',
        'next_service_km',
        'last_service_km',
        'issued_by',
        'estimated_renewal_cost',
        'notes',
        'reminder_enabled',
        'reminder_days_before',
        'last_updated_by_id',
    ];

    protected $casts = [
        'expiry_date'      => 'date',
        'next_service_date'  => 'date',
        'last_service_date'  => 'date',
        'reminder_enabled'   => 'boolean',
    ];

    /** Days remaining until the relevant expiry or service date. */
    public function getDaysRemainingAttribute(): ?int
    {
        /** @var \Illuminate\Support\Carbon|null $date */
        $date = $this->expiry_date ?? $this->next_service_date;
        if (! $date) {
            return null;
        }
        $d = Carbon::instance($date);

        return max(0, (int) now()->startOfDay()->diffInDays($d->copy()->startOfDay(), false));
    }

    /** urgent (≤30 days) | warning (31–90) | safe (>90 or no date). */
    public function getStatusAttribute(): string
    {
        $days = $this->days_remaining;
        if ($days === null) {
            return 'safe';
        }
        if ($days <= 30) {
            return 'urgent';
        }
        if ($days <= 90) {
            return 'warning';
        }

        return 'safe';
    }

    /** Icon identifier for the frontend. */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'registration'       => 'registration',
            'insurance_mandatory',
            'insurance_hull'     => 'insurance',
            'oil'                => 'oil',
            'tire'               => 'tire',
            'air_filter'         => 'filter',
            'brake'              => 'brake',
            default              => 'document',
        };
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /** @param Builder<VehicleMaintenanceItem> $q */
    public function scopeUrgent(Builder $q): Builder
    {
        return $q->whereRaw(
            '(expiry_date IS NOT NULL AND DATEDIFF(expiry_date, CURDATE()) <= 30)
             OR (expiry_date IS NULL AND next_service_date IS NOT NULL AND DATEDIFF(next_service_date, CURDATE()) <= 30)'
        );
    }

    /** @param Builder<VehicleMaintenanceItem> $q */
    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->whereRaw(
            '(expiry_date IS NOT NULL AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 31 AND 90)
             OR (expiry_date IS NULL AND next_service_date IS NOT NULL AND DATEDIFF(next_service_date, CURDATE()) BETWEEN 31 AND 90)'
        );
    }

    /** @param Builder<VehicleMaintenanceItem> $q */
    public function scopeSafe(Builder $q): Builder
    {
        return $q->whereRaw(
            '(expiry_date IS NOT NULL AND DATEDIFF(expiry_date, CURDATE()) > 90)
             OR (next_service_date IS NOT NULL AND DATEDIFF(next_service_date, CURDATE()) > 90)
             OR (expiry_date IS NULL AND next_service_date IS NULL)'
        );
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function renewalLogs(): HasMany
    {
        return $this->hasMany(MaintenanceRenewalLog::class, 'maintenance_item_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by_id');
    }
}
