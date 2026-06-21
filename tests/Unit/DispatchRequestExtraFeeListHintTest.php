<?php

namespace Tests\Unit;

use App\Models\DispatchRequest;
use App\Support\DispatchRequestExtraFeeListHint;
use Tests\TestCase;

class DispatchRequestExtraFeeListHintTest extends TestCase
{
    public function test_flags_pending_row_with_unit_price_but_blank_extra_fee(): void
    {
        $dr = new DispatchRequest([
            'status' => 'pending',
            'trip_type' => 'point_to_point',
            'origin' => 'A',
            'destination' => 'B',
            'wizard_snapshot' => [
                'passengerRows' => [
                    [
                        'pickup' => 'Điểm 1',
                        'dropoff' => 'Điểm 2',
                        'unit_price' => '1000000',
                        'extra_fee' => '',
                    ],
                ],
            ],
        ]);

        $hint = DispatchRequestExtraFeeListHint::forRequest($dr);

        $this->assertTrue($hint['missing_extra_fee']);
        $this->assertSame(1, $hint['missing_extra_fee_count']);
        $this->assertSame('Điểm 1 → Điểm 2', $hint['missing_extra_fee_rows'][0]['label']);
    }

    public function test_ignores_when_extra_fee_is_zero_string(): void
    {
        $dr = new DispatchRequest([
            'status' => 'pending',
            'trip_type' => 'business',
            'wizard_snapshot' => [
                'businessRows' => [
                    [
                        'pickup' => 'X',
                        'dropoff' => 'Y',
                        'unit_price' => '500000',
                        'extra_fee' => '0',
                    ],
                ],
            ],
        ]);

        $hint = DispatchRequestExtraFeeListHint::forRequest($dr);

        $this->assertFalse($hint['missing_extra_fee']);
    }

    public function test_ignores_non_pending_and_cargo(): void
    {
        $pendingCargo = new DispatchRequest([
            'status' => 'pending',
            'trip_type' => 'cargo',
            'wizard_snapshot' => ['cargoRows' => [['cost' => '100']]],
        ]);
        $this->assertFalse(DispatchRequestExtraFeeListHint::forRequest($pendingCargo)['missing_extra_fee']);

        $approved = new DispatchRequest([
            'status' => 'approved',
            'trip_type' => 'point_to_point',
            'wizard_snapshot' => [
                'passengerRows' => [['pickup' => 'A', 'dropoff' => 'B', 'unit_price' => '1', 'extra_fee' => '']],
            ],
        ]);
        $this->assertFalse(DispatchRequestExtraFeeListHint::forRequest($approved)['missing_extra_fee']);
    }
}
