<?php

namespace App\Services\LegacyImport;

/**
 * Maps a single row from Sheet 1 "DX Hanh khach Cong tac" to
 * DispatchRequest + Trip attribute arrays.
 *
 * Sheet 1 column layout (0-indexed, header at row 3, data from row 4):
 *   A(0)  — empty
 *   B(1)  — STT
 *   C(2)  — Ngày nhận
 *   D(3)  — Tháng (formula)
 *   E(4)  — Thứ (formula)
 *   F(5)  — Ngày đi
 *   G(6)  — Ngày về
 *   H(7)  — Thời gian đi
 *   I(8)  — Thời gian về
 *   J(9)  — Địa điểm đi
 *   K(10) — Địa điểm đến
 *   L(11) — Số lượng hành khách
 *   M(12) — Phụ trách
 *   N(13) — Phòng ban
 *   O(14) — Liên hệ
 *   P(15) — Nội dung
 *   Q(16) — Trạng thái xử lý
 *   R(17) — Tài xế (hoặc "Xe nội bộ" / "TAXI")
 *   S(18) — Calendar
 *   T(19) — PĐX
 *   U(20) — Done
 *   V(21) — Loại hình
 *   W(22) — Ghi chú
 *   X(23) — Thông tin xe (BKS nội bộ)
 *   Y(24) — Thông tin tài xế (nội bộ)
 *   Z(25) — Chi phí xe nội bộ
 *   AA(26) — NCC (tên nhà cung cấp)
 *   AB(27) — Thông tin xe NCC (BKS)
 *   AC(28) — Chi phí NCC
 *   AD(29) — Cước xe TAXI
 *   AE(30) — Chi phí TAXI
 *   AF(31) — Số thẻ TAXI
 *   AG(32) — TT
 */
class PassengerRowMapper
{
    use CellValueParser;
    use MapsWithSkipReason;

    private const C_STT = 1;

    private const C_RECEIVED = 2;

    private const C_DATE_DEP = 5;

    private const C_DATE_ARR = 6;

    private const C_TIME_DEP = 7;

    private const C_TIME_ARR = 8;

    private const C_ORIGIN = 9;

    private const C_DEST = 10;

    private const C_PAX = 11;

    private const C_PERSON = 12;

    private const C_DEPT = 13;

    private const C_CONTACT = 14;

    private const C_NOTES = 15;

    private const C_STATUS = 16;

    private const C_DRIVER = 17;

    private const C_TRIP_TYPE = 21;

    private const C_REMARKS = 22;

    private const C_VEH_INT = 23;

    private const C_DRV_INT = 24;

    private const C_COST_INT = 25;

    private const C_NCC_NAME = 26;

    private const C_VEH_NCC = 27;

    private const C_COST_NCC = 28;

    private const C_COST_TAXI = 30;

    /**
     * DispatchRequest status / Trip status pairs keyed by lowercased Excel status value.
     * A null trip status means: create DispatchRequest only (no Trip record).
     */
    private const STATUS_MAP = [
        'done' => ['request' => 'approved',  'trip' => 'completed'],
        'hủy' => ['request' => 'cancelled', 'trip' => 'cancelled'],
        'huỷ' => ['request' => 'cancelled', 'trip' => 'cancelled'],
        'báo xe ok' => ['request' => 'approved',  'trip' => 'driver_confirmed'],
        'in process' => ['request' => 'approved',  'trip' => 'in_progress'],
        'chờ thêm thông tin' => ['request' => 'pending',   'trip' => null],
        'taxi' => ['request' => 'approved',  'trip' => 'completed'],
        'pending' => ['request' => 'pending',   'trip' => null],
    ];

    /** Loại hình → trip_type enum value */
    private const TRIP_TYPE_MAP = [
        'công tác' => 'business',
        'sự kiện/ngoại khóa' => 'point_to_point',
        'khác' => 'point_to_point',
    ];

    /**
     * @param  \OpenSpout\Common\Entity\Cell[]  $cells  0-indexed cell array from a Row
     * @param  int  $rowNum  1-based Excel row number (used for import key)
     * @param  array<string,int>  $vehicleMap  normalised plate → vehicle_id
     * @return array{request:array<string,mixed>,trip:array<string,mixed>|null}|null
     *                                                                               Returns null when the row should be skipped entirely.
     */
    public function map(array $cells, int $rowNum, int $systemUserId, array $vehicleMap): ?array
    {
        if ($this->isRowEmpty($cells, [self::C_STT, self::C_ORIGIN, self::C_STATUS])) {
            return $this->skip('Dòng trống (không có số thứ tự, điểm đi hoặc trạng thái).');
        }

        $rawStatus = $this->cellStr($cells, self::C_STATUS);
        $resolved = LegacyStatusResolver::resolve($rawStatus, self::STATUS_MAP);
        if ($resolved === null) {
            return $this->skip('Thiếu cột «Trạng thái xử lý».');
        }
        $statusEntry = $resolved;
        $statusWarning = $resolved['warning'] ?? null;

        $dateDepart = $this->cellDate($cells, self::C_DATE_DEP);
        if ($dateDepart === null) {
            return $this->skip('«Ngày đi» trống hoặc sai định dạng — dùng ô kiểu Ngày (Date) hoặc gõ dd/mm/yyyy.');
        }

        $origin = $this->cellStr($cells, self::C_ORIGIN);
        $dest = $this->cellStr($cells, self::C_DEST);
        $dateDepartStr = $this->buildDatetime($dateDepart, $this->cellTimeParts($cells, self::C_TIME_DEP));
        $dateArriveStr = $this->buildDatetime(
            $this->cellDate($cells, self::C_DATE_ARR),
            $this->cellTimeParts($cells, self::C_TIME_ARR),
        );
        $receivedDateStr = $this->cellDate($cells, self::C_RECEIVED)?->format('Y-m-d H:i:s');

        $rawTripType = mb_strtolower(trim($this->cellStr($cells, self::C_TRIP_TYPE) ?? ''));
        $tripType = self::TRIP_TYPE_MAP[$rawTripType] ?? 'business';

        $servicePrice = $this->cellFloat($cells, self::C_COST_INT)
            ?? $this->cellFloat($cells, self::C_COST_NCC)
            ?? $this->cellFloat($cells, self::C_COST_TAXI);

        $request = [
            'requester_id' => $systemUserId,
            'trip_type' => $tripType,
            'origin' => $this->cellStr($cells, self::C_ORIGIN),
            'destination' => $this->cellStr($cells, self::C_DEST),
            'depart_at' => $dateDepartStr,
            'arrive_by' => $dateArriveStr,
            'passenger_count' => $this->cellInt($cells, self::C_PAX),
            'notes' => $this->cellStr($cells, self::C_NOTES),
            'status' => $statusEntry['request'],
            'source_channel' => 'paper',
            'paper_status' => 'received',
            'paper_received_at' => $receivedDateStr,
            'is_urgent' => false,
            'service_price' => $servicePrice,
            'wizard_snapshot' => [
                'legacy_import' => true,
                '_import_key' => $this->buildImportKey('pax', $dateDepartStr, $origin, $dest, $rawStatus),
                'legacy_status_warning' => $statusWarning,
                'legacy_requester_name' => $this->cellStr($cells, self::C_PERSON),
                'legacy_department' => $this->cellStr($cells, self::C_DEPT),
                'legacy_contact' => $this->cellStr($cells, self::C_CONTACT),
                'legacy_trip_category' => $this->cellStr($cells, self::C_TRIP_TYPE),
                'legacy_remarks' => $this->cellStr($cells, self::C_REMARKS),
            ],
        ];

        if ($statusEntry['trip'] === null) {
            return ['request' => $request, 'trip' => null];
        }

        $trip = $this->buildTrip($cells, $statusEntry['trip'], $dateDepartStr, $dateArriveStr, $vehicleMap);

        return ['request' => $request, 'trip' => $trip];
    }

    /** @param  array<string,int>  $vehicleMap */
    private function buildTrip(
        array $cells,
        string $tripStatus,
        ?string $departStr,
        ?string $arriveStr,
        array $vehicleMap,
    ): array {
        $rawDriver = $this->cellStr($cells, self::C_DRIVER);
        $intVehRaw = $this->cellStr($cells, self::C_VEH_INT);
        $intDrvRaw = $this->cellStr($cells, self::C_DRV_INT);
        $nccVehRaw = $this->cellStr($cells, self::C_VEH_NCC);
        $nccName = $this->cellStr($cells, self::C_NCC_NAME);

        $vehicleId = null;
        $externalVehicleRef = null;
        $externalDriverRef = null;

        // Attempt to resolve internal vehicle by license plate
        if ($intVehRaw !== null) {
            $vehicleId = $this->resolveVehicleId($vehicleMap, $intVehRaw);
            if ($vehicleId !== null) {
                // resolved
            } else {
                $externalVehicleRef = $intVehRaw;
            }
        } elseif ($nccVehRaw !== null) {
            $externalVehicleRef = $nccName ? "{$nccVehRaw} ({$nccName})" : $nccVehRaw;
        } elseif (mb_strtolower($rawDriver ?? '') === 'taxi') {
            $externalVehicleRef = 'TAXI';
        }

        // Resolve driver reference
        if ($intDrvRaw !== null) {
            $externalDriverRef = $intDrvRaw;
        } elseif ($rawDriver !== null) {
            $lower = mb_strtolower($rawDriver);
            if ($lower !== 'xe nội bộ' && $lower !== 'taxi') {
                $externalDriverRef = $rawDriver;
            }
        }

        $completedAt = $tripStatus === 'completed' ? ($arriveStr ?? $departStr) : null;

        return [
            'status' => $tripStatus,
            'depart_at' => $departStr,
            'arrive_by' => $arriveStr,
            'vehicle_id' => $vehicleId,
            'external_vehicle_ref' => $externalVehicleRef,
            'external_driver_ref' => $externalDriverRef,
            'completed_at' => $completedAt,
            'payment_status' => 'unpaid',
        ];
    }

    private function buildImportKey(string $prefix, ?string $depart, ?string $origin, ?string $dest, ?string $status): string
    {
        $payload = implode('|', array_map(fn ($v) => mb_strtolower(trim((string) ($v ?? ''))), [$depart, $origin, $dest, $status]));

        return $prefix.'_'.substr(hash('sha256', $payload), 0, 20);
    }
}
