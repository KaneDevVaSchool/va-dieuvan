<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Controller;
use App\Models\DispatchRequest;
use App\Services\Requests\RequestListXlsxWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RequestExportController extends Controller
{
    public function __construct(
        private readonly RequestListXlsxWriter $writer,
    ) {}

    public function download(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()?->hasPermission('request.manage'), 403);

        $user = $request->user();

        $q = DispatchRequest::with('requester:id,name,email')
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('trip_type')) {
            $q->where('trip_type', $request->input('trip_type'));
        }
        if ($request->filled('from')) {
            $q->where('depart_at', '>=', $request->input('from').' 00:00:00');
        }
        if ($request->filled('to')) {
            $q->where('depart_at', '<=', $request->input('to').' 23:59:59');
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $q->where(function ($sub) use ($term) {
                $sub->where('origin', 'like', "%{$term}%")
                    ->orWhere('destination', 'like', "%{$term}%");
            });
        }

        $requests = $q->get();
        $rows = $requests->map(fn (DispatchRequest $r) => array_merge($r->toArray(), [
            'requester_name' => $r->requester?->name ?? '',
        ]))->all();

        $parts = [];
        if ($request->filled('status')) {
            $parts[] = 'TT: '.$request->input('status');
        }
        if ($request->filled('trip_type')) {
            $parts[] = 'Loại: '.$request->input('trip_type');
        }

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $parts === [] ? 'Tất cả' : implode(' · ', $parts),
        ]);

        $filename = 'danh-sach-phieu-de-xuat_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
