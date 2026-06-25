<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reports\TripCostExportRequest;
use App\Http\Requests\Api\Reports\TripCostReportRequest;
use App\Services\Reports\Export\TripCostReportXlsxWriter;
use App\Services\Reports\TripCostReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TripCostReportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly TripCostReportService $service,
        private readonly TripCostReportXlsxWriter $xlsxWriter,
    ) {}

    /**
     * GET /api/reports/trip-costs/statistics
     * Permission: report.view
     */
    public function statistics(TripCostReportRequest $request)
    {
        $filters = array_filter($request->validated(), fn ($v) => $v !== null && $v !== '');
        $user = $request->user();
        $rows = $this->service->rows($user, $filters);
        $stats = $this->service->statisticsFromRows($rows);

        return $this->ok([
            'stats' => $stats,
            'rows' => $rows,
        ]);
    }

    /**
     * GET /api/reports/trip-costs/export-xlsx
     * Permission: report.export
     */
    public function exportXlsx(TripCostExportRequest $request)
    {
        $exportAll = $request->boolean('all');
        $filters = $this->resolveExportFilters($request, $exportAll);
        $user = $request->user();
        $rows = $this->service->rows($user, $filters);
        $exportedBy = $user->name ?? null;

        $summaryText = $exportAll
            ? 'Phạm vi: Tất cả dữ liệu (không lọc)'
            : implode('   ·   ', $this->buildFilterSummary($filters));

        $tmpPath = $this->xlsxWriter->writeTempFile($rows, $filters, $exportedBy, null, $summaryText ?: null);

        $filename = 'doanh-thu-chuyen_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/reports/trip-costs/export-pdf
     * Permission: report.export
     */
    public function exportPdf(TripCostExportRequest $request)
    {
        $exportAll = $request->boolean('all');
        $filters = $this->resolveExportFilters($request, $exportAll);
        $user = $request->user();
        $rows = $this->service->rows($user, $filters);
        $exportedBy = $user->name ?? null;

        $upValues = array_filter(array_column($rows, 'unit_price'), fn ($v) => $v !== null);
        $efValues = array_filter(array_column($rows, 'extra_fee'), fn ($v) => $v !== null);
        $sumAmount = (float) array_sum(array_column($rows, 'amount'));
        $sumUnitPrice = (float) ($upValues ? array_sum($upValues) : 0);
        $sumExtraFee = (float) ($efValues ? array_sum($efValues) : 0);

        $filterSummary = $exportAll
            ? ['Phạm vi: Tất cả dữ liệu (không lọc)']
            : $this->buildFilterSummary($filters);

        $tripType = $filters['trip_type'] ?? null;
        $sheetTitle = $tripType === 'business'
            ? 'DOANH THU CÔNG TÁC'
            : 'BÁO CÁO DOANH THU CHUYẾN';

        $pdf = Pdf::loadView('pdf.trip-cost-report', [
            'rows' => $rows,
            'sheetTitle' => $sheetTitle,
            'exportedBy' => $exportedBy,
            'unitName' => null,
            'filterSummary' => $filterSummary,
            'sumAmount' => $sumAmount,
            'sumUnitPrice' => $sumUnitPrice,
            'sumExtraFee' => $sumExtraFee,
        ])->setPaper('a4', 'landscape');

        $filename = 'doanh-thu-chuyen_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Bộ lọc thực dùng cho xuất file: "Xuất tất cả" bỏ qua mọi bộ lọc.
     *
     * @return array<string, mixed>
     */
    private function resolveExportFilters(TripCostExportRequest $request, bool $exportAll): array
    {
        $validated = $request->validated();
        unset($validated['all']);

        if ($exportAll) {
            return [];
        }

        return array_filter($validated, fn ($v) => $v !== null && $v !== '');
    }

    private const COST_TYPE_LABELS = [
        'fuel' => 'Nhiên liệu',
        'toll' => 'Cầu đường',
        'parking' => 'Gửi xe',
        'meal' => 'Ăn uống',
        'wash' => 'Rửa xe',
        'fine' => 'Phạt',
        'repair' => 'Sửa chữa',
        'wizard_estimate' => 'Dự toán phiếu',
        'other' => 'Khác',
    ];

    /** @return string[] */
    private function buildFilterSummary(array $filters): array
    {
        $labels = [];

        if (! empty($filters['from']) || ! empty($filters['to'])) {
            $from = isset($filters['from']) ? date('d/m/Y', strtotime($filters['from'])) : '…';
            $to = isset($filters['to']) ? date('d/m/Y', strtotime($filters['to'])) : '…';
            $labels[] = "Kỳ: {$from} → {$to}";
        }

        $tripTypeMap = TripCostReportService::TRIP_TYPE_LABELS;
        if (! empty($filters['trip_type']) && isset($tripTypeMap[$filters['trip_type']])) {
            $labels[] = 'Loại: '.$tripTypeMap[$filters['trip_type']];
        }

        if (! empty($filters['type'])) {
            $labels[] = 'Phân loại CP: '.(self::COST_TYPE_LABELS[$filters['type']] ?? $filters['type']);
        }

        $statusMap = TripCostReportService::STATUS_LABELS;
        if (! empty($filters['status']) && isset($statusMap[$filters['status']])) {
            $labels[] = 'Trạng thái: '.$statusMap[$filters['status']];
        }

        if (! empty($filters['provider'])) {
            $labels[] = 'NCC: '.Str::limit($filters['provider'], 30);
        }

        if (! empty($filters['unit'])) {
            $labels[] = 'Đơn vị: '.Str::limit($filters['unit'], 30);
        }

        $fleetMap = [
            'internal' => 'Xe nội bộ',
            'vendor_hire' => 'Thuê xe NCC',
            'taxi' => 'Taxi',
            'unspecified' => 'Không xác định',
        ];
        if (! empty($filters['fleet_mode']) && isset($fleetMap[$filters['fleet_mode']])) {
            $labels[] = 'Nguồn: '.$fleetMap[$filters['fleet_mode']];
        }

        if (isset($filters['min_amount']) || isset($filters['max_amount'])) {
            $min = isset($filters['min_amount']) ? number_format((float) $filters['min_amount']) : '0';
            $max = isset($filters['max_amount']) ? number_format((float) $filters['max_amount']) : '…';
            $labels[] = "Số tiền: {$min} → {$max}";
        }

        return $labels;
    }
}
