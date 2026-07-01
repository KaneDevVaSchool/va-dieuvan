<?php

namespace App\Http\Controllers\Api\Resources;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\Resources\VehicleListXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VehicleExportController extends Controller
{
    public function __construct(
        private readonly VehicleListXlsxWriter $writer,
    ) {}

    public function download(Request $request): BinaryFileResponse
    {
        abort_unless((bool) $request->user(), 403);

        $user = $request->user();

        $q = Vehicle::query()->orderBy('license_plate');

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $q->where('type', $request->input('type'));
        }

        $vehicles = $q->get();
        $rows = $vehicles->map(fn (Vehicle $v) => $v->toArray())->all();

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
        ]);

        $filename = 'danh-sach-xe_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
