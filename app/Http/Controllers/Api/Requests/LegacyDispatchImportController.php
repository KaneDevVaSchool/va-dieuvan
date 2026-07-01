<?php

namespace App\Http\Controllers\Api\Requests;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Requests\LegacyDispatchImportRequest;
use App\Models\DispatchImportBatch;
use App\Services\LegacyImport\DispatchLegacyImportWorkflow;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyDispatchImportController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly DispatchLegacyImportWorkflow $workflow,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')), 403);

        $rows = DispatchImportBatch::query()
            ->where('imported_by', $user->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (DispatchImportBatch $b) => $this->workflow->serializeBatch($b));

        return $this->ok(['items' => $rows]);
    }

    public function store(LegacyDispatchImportRequest $request)
    {
        $file = $request->file('file');
        $sheets = (array) ($request->input('sheets') ?? []);

        $batch = $this->workflow->uploadAndAnalyze($request->user(), $file, $sheets);

        return $this->created($this->workflow->serializeBatch($batch));
    }

    public function show(Request $request, DispatchImportBatch $dispatchImportBatch)
    {
        $this->authorizeBatch($request, $dispatchImportBatch);

        return $this->ok($this->workflow->serializeBatch($dispatchImportBatch));
    }

    public function execute(Request $request, DispatchImportBatch $dispatchImportBatch)
    {
        $this->authorizeBatch($request, $dispatchImportBatch);

        $batch = $this->workflow->execute($dispatchImportBatch);

        return $this->ok($this->workflow->serializeBatch($batch));
    }

    public function errorReport(Request $request, DispatchImportBatch $dispatchImportBatch): BinaryFileResponse
    {
        $this->authorizeBatch($request, $dispatchImportBatch);
        abort_unless($dispatchImportBatch->error_report_path, 404, 'Chưa có báo cáo lỗi cho lần import này.');

        $path = storage_path('app/'.$dispatchImportBatch->error_report_path);
        abort_unless(is_file($path), 404, 'File báo cáo không còn tồn tại.');

        return response()->download(
            $path,
            'Bao_cao_import_'.$dispatchImportBatch->id.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function template(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')), 403);

        $candidates = glob(base_path('database/*.xlsx')) ?: [];
        $path = null;
        foreach ($candidates as $candidate) {
            $base = mb_strtolower(basename($candidate));
            if (str_contains($base, 'phiếu') || str_contains($base, 'phieu')) {
                $path = $candidate;
                break;
            }
        }
        $path ??= $candidates[0] ?? null;

        abort_unless($path && is_file($path), 404, 'Chưa có file mẫu trên máy chủ. Liên hệ quản trị hệ thống.');

        return response()->download(
            $path,
            'Mau_Phieu_de_xuat_ghi_nhan.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    private function authorizeBatch(Request $request, DispatchImportBatch $batch): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasPermission('trip.view_all') || $user->hasPermission('request.approve')), 403);
        abort_unless((int) $batch->imported_by === (int) $user->id, 403);
    }
}
