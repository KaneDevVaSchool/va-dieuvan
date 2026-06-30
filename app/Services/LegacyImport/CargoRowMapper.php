<?php

namespace App\Services\LegacyImport;

/**
 * Maps a single row from Sheet 2 "DX Hang hoa" to
 * DispatchRequest + Trip attribute arrays.
 *
 * Sheet 2 column layout (0-indexed, header at row 2, data from row 3):
 *   A(0)  — empty
 *   B(1)  — STT
 *   C(2)  — Ngày nhận
 *   D(3)  — Thứ (formula)
 *   E(4)  — Ngày đi
 *   F(5)  — Ngày về
 *   G(6)  — Thời gian đi
 *   H(7)  — Thời gian về
 *   I(8)  — Địa điểm đi
 *   J(9)  — Địa điểm đến
 *   K(10) — Kích thước / Khối lượng
 *   L(11) — Phụ trách
 *   M(12) — Phòng ban
 *   N(13) — Liên hệ
 *   O(14) — Nội dung
 *   P(15) — Trạng thái xử lý
 *   Q(16) — Tài xế
 *   R(17) — PĐX
 *   S(18) — Done
 *   T(19) — Ghi chú
 *   U(20) — Số tiền (nếu có)
 *   V(21) — HS TT
 *   W(22) — empty
 */
class CargoRowMapper
{
    use CellValueParser;

    private const C_STT = 1;

    private const C_RECEIVED = 2;

    private const C_DATE_DEP = 4;

    private const C_DATE_ARR = 5;

    private const C_TIME_DEP = 6;

    private const C_TIME_ARR = 7;

    private const C_ORIGIN = 8;

    private const C_DEST = 9;

    private const C_CARGO = 10;

    private const C_PERSON = 11;

    private const C_DEPT = 12;

    private const C_CONTACT = 13;

    private const C_NOTES = 14;

    private const C_STATUS = 15;

    private const C_DRIVER = 16;

    private const C_REMARKS = 19;

    private const C_AMOUNT = 20;

    private const STATUS_MAP = [
        'done' => ['request' => 'approved',  'trip' => 'completed'],
        'hủy' => ['request' => 'cancelled', 'trip' => 'cancelled'],
        'huỷ' => ['request' => 'cancelled', 'trip' => 'cancelled'],
        'in process' => ['request' => 'approved',  'trip' => 'in_progress'],
        'pending' => ['request' => 'pending',   'trip' => null],
    ];

    /**
     * @param  \OpenSpout\Common\Entity\Cell[]  $cells
     * @param  int  $rowNum  1-based Excel row number
     * @param  array<string,int>  $vehicleMap  (unused for cargo — driver ref only)
     * @return array{request:array<string,mixed>,trip:array<string,mixed>|null}|null
     */
    public function map(array $cells, int $rowNum, int $systemUserId, array $vehicleMap): ?array
    {
        if ($this->isRowEmpty($cells, [self::C_STT, self::C_ORIGIN, self::C_STATUS])) {
            return null;
        }

        $rawStatus = mb_strtolower(trim($this->cellStr($cells, self::C_STATUS) ?? ''));
        $statusEntry = self::STATUS_MAP[$rawStatus] ?? null;

        $dateDepart = $this->cellDate($cells, self::C_DATE_DEP);
        if ($dateDepart === null) {
            return null;
        }

        $dateDepartStr = $this->buildDatetime($dateDepart, $this->cellTimeParts($cells, self::C_TIME_DEP));
        $dateArriveStr = $this->buildDatetime(
            $this->cellDate($cells, self::C_DATE_ARR),
            $this->cellTimeParts($cells, self::C_TIME_ARR),
        );
        $receivedDateStr = $this->cellDate($cells, self::C_RECEIVED)?->format('Y-m-d H:i:s');

        // Merge content + cargo size into notes
        $noteParts = array_filter([
            $this->cellStr($cells, self::C_NOTES),
            $this->cellStr($cells, self::C_CARGO) !== null
                ? 'Kích thước/KL: '.$this->cellStr($cells, self::C_CARGO)
                : null,
        ]);
        $notes = $noteParts ? implode(' | ', $noteParts) : null;

        $request = [
            'requester_id' => $systemUserId,
            'trip_type' => 'cargo',
            'origin' => $this->cellStr($cells, self::C_ORIGIN),
            'destination' => $this->cellStr($cells, self::C_DEST),
            'depart_at' => $dateDepartStr,
            'arrive_by' => $dateArriveStr,
            'notes' => $notes,
            'status' => $statusEntry['request'] ?? 'pending',
            'source_channel' => 'paper',
            'paper_status' => 'received',
            'paper_received_at' => $receivedDateStr,
            'is_urgent' => false,
            'service_price' => $this->cellFloat($cells, self::C_AMOUNT),
            'wizard_snapshot' => [
                'legacy_import' => true,
                '_import_key' => 'cargo_'.$rowNum,
                'legacy_requester_name' => $this->cellStr($cells, self::C_PERSON),
                'legacy_department' => $this->cellStr($cells, self::C_DEPT),
                'legacy_contact' => $this->cellStr($cells, self::C_CONTACT),
                'legacy_remarks' => $this->cellStr($cells, self::C_REMARKS),
            ],
        ];

        if ($statusEntry === null || $statusEntry['trip'] === null) {
            return ['request' => $request, 'trip' => null];
        }

        $rawDriver = $this->cellStr($cells, self::C_DRIVER);
        $completedAt = $statusEntry['trip'] === 'completed' ? ($dateArriveStr ?? $dateDepartStr) : null;

        $trip = [
            'status' => $statusEntry['trip'],
            'depart_at' => $dateDepartStr,
            'arrive_by' => $dateArriveStr,
            'vehicle_id' => null,
            'external_vehicle_ref' => null,
            'external_driver_ref' => $rawDriver,
            'completed_at' => $completedAt,
            'payment_status' => 'unpaid',
        ];

        return ['request' => $request, 'trip' => $trip];
    }
}
