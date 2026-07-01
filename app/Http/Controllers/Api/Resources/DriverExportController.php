<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\Resources\DriverListXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DriverExportController extends Controller
{
    public function __construct(
        private readonly DriverListXlsxWriter $writer,
    ) {}

    public function download(Request $request): BinaryFileResponse
    {
        abort_unless((bool) $request->user(), 403);

        $user = $request->user();

        $q = Driver::query()->orderBy('full_name');

        if ($request->filled('availability_status')) {
            $q->where('availability_status', $request->input('availability_status'));
        }

        $drivers = $q->get();
        $rows = $drivers->map(fn (Driver $d) => $d->toArray())->all();

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
        ]);

        $filename = 'danh-sach-tai-xe_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
