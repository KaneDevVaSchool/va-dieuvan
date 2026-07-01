<?php

namespace App\Http\Controllers\Api\Costs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Costs\ListAllTripCostsRequest;
use App\Models\TripCost;
use App\Services\Costs\TripCostListXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TripCostExportController extends Controller
{
    public function __construct(
        private readonly TripCostListXlsxWriter $writer,
    ) {}

    public function download(ListAllTripCostsRequest $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('trip.cost.reconcile'), 403);

        $data = $request->validated();
        $user = $request->user();

        $q = TripCost::with('creator:id,name')->orderByDesc('id');

        if (! empty($data['status'])) {
            $q->where('status', $data['status']);
        }
        if (! empty($data['type'])) {
            $q->where('type', $data['type']);
        }
        if (! empty($data['trip_id'])) {
            $q->where('trip_id', $data['trip_id']);
        }
        if (! empty($data['from'])) {
            $q->where('reported_on', '>=', $data['from']);
        }
        if (! empty($data['to'])) {
            $q->where('reported_on', '<=', $data['to']);
        }

        $costs = $q->get();
        $rows = $costs->map(fn (TripCost $c) => array_merge($c->toArray(), [
            'creator_name' => $c->creator?->name ?? '',
        ]))->all();

        $parts = [];
        if (! empty($data['status'])) {
            $parts[] = 'TT: '.$data['status'];
        }
        if (! empty($data['type'])) {
            $parts[] = 'Loại: '.$data['type'];
        }

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $parts === [] ? 'Tất cả' : implode(' · ', $parts),
        ]);

        $filename = 'danh-sach-chi-phi-chuyen_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
