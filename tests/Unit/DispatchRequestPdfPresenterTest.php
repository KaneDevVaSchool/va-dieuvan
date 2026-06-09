<?php

namespace Tests\Unit;

use App\Models\DispatchRequest;
use App\Services\DispatchRequests\DispatchRequestPdfPresenter;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DispatchRequestPdfPresenterTest extends TestCase
{
    public function test_build_pdf_data_formats_dates_and_datetimes(): void
    {
        $dr = new DispatchRequest();
        $dr->id = 2;
        $dr->trip_type = 'point_to_point';
        $dr->status = 'approved';
        $dr->created_at = Carbon::parse('2026-06-10 09:00:00');
        $dr->wizard_snapshot = [
            'form' => [
                'proposed_date' => '2026-06-01',
                'date_needed' => '2026-06-10',
            ],
            'passengerRows' => [
                [
                    'pickup_place' => 'A',
                    'dropoff_place' => 'B',
                    'depart_at' => '2026-06-10T08:30',
                    'return_at' => '2026-06-10T17:00',
                ],
            ],
        ];

        $data = DispatchRequestPdfPresenter::buildPdfData($dr);

        $this->assertSame('01/06/2026', $data['proposedDate']);
        $this->assertSame('10/06/2026', $data['dateNeeded']);
        $this->assertSame('10/06 08:30', $data['passengerSectionRows'][0]['puTime']);
        $this->assertSame('10/06 17:00', $data['passengerSectionRows'][0]['delTime']);
    }

    public function test_build_pdf_data_formats_iso_utc_datetimes_in_vn_timezone(): void
    {
        $dr = new DispatchRequest();
        $dr->id = 4;
        $dr->trip_type = 'point_to_point';
        $dr->status = 'approved';
        $dr->created_at = Carbon::parse('2026-06-10 09:00:00');
        $dr->wizard_snapshot = [
            'form' => [],
            'passengerRows' => [
                [
                    'pickup_place' => 'A',
                    'dropoff_place' => 'B',
                    'depart_at' => '2026-06-10T01:30:00.000000Z',
                    'return_at' => '2026-06-10T10:30:00.000000Z',
                ],
            ],
        ];

        $data = DispatchRequestPdfPresenter::buildPdfData($dr);

        $this->assertSame('10/06 08:30', $data['passengerSectionRows'][0]['puTime']);
        $this->assertSame('10/06 17:30', $data['passengerSectionRows'][0]['delTime']);
    }

    public function test_parse_money_handles_vn_grouped_amounts(): void
    {
        $this->assertSame(1500000, DispatchRequestPdfPresenter::parseMoney('1.500.000'));
        $this->assertSame(250000, DispatchRequestPdfPresenter::parseMoney(250000));
    }

    public function test_build_pdf_data_formats_cargo_datetimes(): void
    {
        $dr = new DispatchRequest();
        $dr->id = 3;
        $dr->trip_type = 'cargo';
        $dr->status = 'approved';
        $dr->created_at = Carbon::parse('2026-06-10 09:00:00');
        $dr->wizard_snapshot = [
            'form' => [],
            'cargoRows' => [
                [
                    'name' => 'Thùng cartons',
                    'pickup_at' => '2026-06-11T07:15',
                    'delivery_at' => '2026-06-11T15:45',
                ],
            ],
        ];

        $data = DispatchRequestPdfPresenter::buildPdfData($dr);

        $this->assertSame('11/06 07:15', $data['cargoSectionRows'][0]['puTime']);
        $this->assertSame('11/06 15:45', $data['cargoSectionRows'][0]['delTime']);
    }
}
