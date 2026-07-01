<?php

namespace App\Http\Controllers\Api\Cargo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cargo\ListCargoShipmentsRequest;
use App\Models\CargoShipment;
use App\Services\Cargo\CargoListXlsxWriter;
use App\Services\Cargo\CargoShipmentListService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CargoExportController extends Controller
{
    public function __construct(
        private readonly CargoListXlsxWriter $writer,
        private readonly CargoShipmentListService $listService,
    ) {}

    public function download(ListCargoShipmentsRequest $request): BinaryFileResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $shipments = $this->listService->filteredQuery($data)
            ->orderByDesc('id')
            ->get();

        $rows = $shipments->map(fn (CargoShipment $s) => $s->toArray())->all();

        $parts = [];
        if (! empty($data['status'])) {
            $parts[] = 'TT: '.$data['status'];
        }
        if (! empty($data['q'])) {
            $parts[] = 'Tìm: «'.$data['q'].'»';
        }

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $parts === [] ? 'Tất cả' : implode(' · ', $parts),
        ]);

        $filename = 'danh-sach-don-hang_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
