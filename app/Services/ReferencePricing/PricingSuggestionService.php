<?php

namespace App\Services\ReferencePricing;

use App\Models\CargoFareRate;
use App\Models\PassengerFareRate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PricingSuggestionService
{
    /**
     * @return array{enabled: bool, suggestions: list<array<string, mixed>>}
     */
    public function suggest(
        string $tripType,
        ?string $origin,
        ?string $destination,
        ?int $passengerCount,
    ): array {
        if (! config('dispatch.pricing_suggest_enabled', false)) {
            return ['enabled' => false, 'suggestions' => []];
        }

        $haystack = $this->normalizeHaystack($origin, $destination);

        if ($tripType === 'cargo') {
            return [
                'enabled' => true,
                'suggestions' => $this->suggestCargo($haystack)->values()->all(),
            ];
        }

        if (in_array($tripType, ['point_to_point', 'business', 'door_to_door'], true)) {
            return [
                'enabled' => true,
                'suggestions' => $this->suggestPassenger($haystack, $passengerCount)->values()->all(),
            ];
        }

        return ['enabled' => true, 'suggestions' => []];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function suggestCargo(string $haystack): Collection
    {
        $rates = CargoFareRate::query()->orderBy('sort_order')->get();

        $scored = $rates->map(function (CargoFareRate $row) use ($haystack) {
            $label = (string) ($row->route_label ?? $row->route_code ?? '');
            $norm = $this->normalize($label);
            $score = 0;
            if ($haystack !== '' && $norm !== '') {
                if (Str::contains($haystack, $norm) || Str::contains($norm, $haystack)) {
                    $score = 10;
                } else {
                    $tokens = array_filter(explode(' ', $norm), fn ($t) => strlen($t) >= 3);
                    foreach ($tokens as $token) {
                        if (Str::contains($haystack, $token)) {
                            $score = max($score, 4);
                        }
                    }
                }
            }

            $unit = $row->van_500kg ?? $row->van_1000kg ?? $row->one_crate_50_40_50;

            return [
                'score' => $score,
                'source' => 'cargo_fare_rate',
                'id' => $row->id,
                'label' => $label !== '' ? $label : (string) $row->route_code,
                'distance_km' => $row->distance_km !== null ? (float) $row->distance_km : null,
                'reference_unit_price' => $unit !== null ? (float) $unit : null,
                'vehicle_hint' => 'van_500kg',
            ];
        });

        return $scored
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->map(fn (array $item) => array_diff_key($item, ['score' => true]));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function suggestPassenger(string $haystack, ?int $passengerCount): Collection
    {
        $rates = PassengerFareRate::query()->orderBy('sort_order')->get();

        $scored = $rates->map(function (PassengerFareRate $row) use ($haystack, $passengerCount) {
            $label = (string) ($row->package_label ?? $row->package_code ?? '');
            $norm = $this->normalize($label);
            $score = 1;
            if ($haystack !== '' && $norm !== '') {
                if (Str::contains($haystack, $norm) || Str::contains($norm, $haystack)) {
                    $score = 10;
                }
            }

            $unit = $this->pickPassengerSeatPrice($row, $passengerCount);

            return [
                'score' => $score,
                'source' => 'passenger_fare_rate',
                'id' => $row->id,
                'label' => $label !== '' ? $label : (string) $row->package_code,
                'distance_km' => null,
                'reference_unit_price' => $unit,
                'vehicle_hint' => $this->seatHint($passengerCount),
            ];
        });

        return $scored
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->map(fn (array $item) => array_diff_key($item, ['score' => true]));
    }

    private function pickPassengerSeatPrice(PassengerFareRate $row, ?int $passengerCount): ?float
    {
        $n = $passengerCount ?? 0;
        if ($n <= 7 && $row->seat_7 !== null) {
            return (float) $row->seat_7;
        }
        if ($n <= 15 && $row->seat_15 !== null) {
            return (float) $row->seat_15;
        }
        if ($n <= 28 && $row->seat_28 !== null) {
            return (float) $row->seat_28;
        }
        if ($n <= 33 && $row->seat_33 !== null) {
            return (float) $row->seat_33;
        }
        foreach (['seat_15', 'seat_7', 'seat_28', 'seat_33', 'seat_45', 'limo_9', 'limo_11'] as $col) {
            if ($row->{$col} !== null) {
                return (float) $row->{$col};
            }
        }

        return null;
    }

    private function seatHint(?int $passengerCount): string
    {
        $n = $passengerCount ?? 0;
        if ($n <= 0) {
            return 'seat_15';
        }
        if ($n <= 7) {
            return 'seat_7';
        }
        if ($n <= 15) {
            return 'seat_15';
        }
        if ($n <= 28) {
            return 'seat_28';
        }

        return 'seat_33';
    }

    private function normalizeHaystack(?string $origin, ?string $destination): string
    {
        return $this->normalize(trim((string) $origin).' '.trim((string) $destination));
    }

    private function normalize(string $value): string
    {
        $v = Str::lower(Str::ascii(trim($value)));

        return preg_replace('/\s+/', ' ', $v) ?? '';
    }
}
