<?php

namespace App\Http\Controllers\Api\Trips;

use App\Http\Requests\Api\Trips\ListTripsRequest;
use App\Models\Trip;
use App\Services\Trips\TripListXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TripExportController extends TripController
{
    public function __construct(
        private readonly TripListXlsxWriter $writer,
    ) {}

    public function download(ListTripsRequest $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('trip.manage'), 403);

        $data = $request->validated();
        $user = $request->user();

        $q = $this->newTripListBuilder($user)
            ->with([
                'dispatchRequest:id,trip_type,origin,destination',
                'vehicle:id,license_plate',
                'driver:id,full_name',
            ])
            ->orderByDesc('trips.depart_at');

        $this->applyTripListFilters($q, $data);

        $trips = $q->get();
        $rows = $trips->map(fn (Trip $t) => $t->toArray())->all();

        $parts = [];
        if (! empty($data['status'])) {
            $parts[] = 'TT: '.$data['status'];
        }
        if (! empty($data['trip_type'])) {
            $parts[] = 'Loại: '.$data['trip_type'];
        }
        if (! empty($data['from'])) {
            $parts[] = 'Từ: '.$data['from'];
        }
        if (! empty($data['to'])) {
            $parts[] = 'Đến: '.$data['to'];
        }

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $parts === [] ? 'Tất cả' : implode(' · ', $parts),
        ]);

        $filename = 'danh-sach-chuyen-di_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
