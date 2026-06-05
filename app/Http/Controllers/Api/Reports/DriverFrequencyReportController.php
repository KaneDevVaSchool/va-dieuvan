<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reports\DriverFrequencyReportRequest;
use App\Services\Reports\DriverFrequencyReportService;
use App\Services\Reports\Export\DriverFrequencyReportXlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverFrequencyReportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DriverFrequencyReportService $service,
        private readonly DriverFrequencyReportXlsxWriter $xlsxWriter,
    ) {}

    /**
     * GET /api/reports/driver-frequency
     */
    public function statistics(DriverFrequencyReportRequest $request)
    {
        $filters = array_filter($request->validated(), fn ($v) => $v !== null && $v !== '');
        $payload = $this->service->report($request->user(), $filters);

        return $this->ok($payload);
    }

    /**
     * GET /api/reports/driver-frequency/export-xlsx
     */
    public function exportXlsx(DriverFrequencyReportRequest $request)
    {
        if (! $request->user()?->hasPermission('report.export')) {
            abort(403);
        }

        $filters = array_filter($request->validated(), fn ($v) => $v !== null && $v !== '');
        $user = $request->user();
        $payload = $this->service->report($user, $filters);
        $exportedBy = $user->name ?? null;

        $tmpPath = $this->xlsxWriter->writeTempFile($payload, $filters, $exportedBy);
        $filename = 'tan-suat-tai-xe_' . now()->format('Ymd_His') . '.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/reports/driver-frequency/export-pdf
     */
    public function exportPdf(DriverFrequencyReportRequest $request)
    {
        if (! $request->user()?->hasPermission('report.export')) {
            abort(403);
        }

        $filters = array_filter($request->validated(), fn ($v) => $v !== null && $v !== '');
        $user = $request->user();
        $payload = $this->service->report($user, $filters);
        $exportedBy = $user->name ?? null;

        $pdf = Pdf::loadView('pdf.driver-frequency-report', [
            'payload'      => $payload,
            'filters'      => $filters,
            'exportedBy'   => $exportedBy,
            'filterLabels' => $this->buildFilterSummary($filters),
        ])->setPaper('a4', 'landscape');

        $filename = 'tan-suat-tai-xe_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }

    /** @return list<string> */
    private function buildFilterSummary(array $filters): array
    {
        $labels = [];

        if (! empty($filters['year'])) {
            $labels[] = 'Năm: '.$filters['year'];
        }

        $quarterMap = [
            'q1' => 'Quý 1',
            'q2' => 'Quý 2',
            'q3' => 'Quý 3',
            'q4' => 'Quý 4',
        ];
        if (! empty($filters['quarter']) && isset($quarterMap[$filters['quarter']])) {
            $labels[] = $quarterMap[$filters['quarter']];
        }

        if (! empty($filters['trip_type'])) {
            $typeMap = DriverFrequencyReportService::TRIP_TYPE_LABELS;
            $labels[] = 'Loại chuyến: '.($typeMap[$filters['trip_type']] ?? $filters['trip_type']);
        }

        if (! empty($filters['vehicle_plate'])) {
            $labels[] = 'Biển số: '.$filters['vehicle_plate'];
        }

        return $labels;
    }
}
