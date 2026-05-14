<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchSetting extends Model
{
    protected $fillable = [
        'passenger_urgent_threshold_hours',
        'cargo_urgent_threshold_hours',
        'reference_pricing_url',
    ];

    public static function singletonRow(): ?self
    {
        return static::query()->first();
    }

    public static function passengerUrgentThresholdHours(): int
    {
        $row = static::singletonRow();

        return (int) (
            $row?->passenger_urgent_threshold_hours
            ?? config('dispatch.passenger_urgent_threshold_hours')
        );
    }

    public static function cargoUrgentThresholdHours(): int
    {
        $row = static::singletonRow();

        return (int) (
            $row?->cargo_urgent_threshold_hours
            ?? config('dispatch.cargo_urgent_threshold_hours')
        );
    }

    public static function urgentThresholdHoursForTripType(string $tripType): int
    {
        return $tripType === 'cargo'
            ? static::cargoUrgentThresholdHours()
            : static::passengerUrgentThresholdHours();
    }

    public static function referencePricingUrl(): ?string
    {
        $row = static::singletonRow();
        $url = $row?->reference_pricing_url;

        return ($url !== null && trim((string) $url) !== '') ? trim((string) $url) : null;
    }
}
