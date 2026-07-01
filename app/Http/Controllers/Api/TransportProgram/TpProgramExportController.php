<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TransportProgram\ListTpProgramsRequest;
use App\Models\TpProgram;
use App\Services\TransportProgram\TpProgramListFilter;
use App\Services\TransportProgram\TpProgramListXlsxWriter;
use App\Services\TransportProgram\TpProgramPresenter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TpProgramExportController extends Controller
{
    public function __construct(
        private readonly TpProgramListXlsxWriter $writer,
        private readonly TpProgramPresenter $presenter,
        private readonly TpProgramListFilter $listFilter,
    ) {}

    public function download(ListTpProgramsRequest $request): BinaryFileResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $programs = $this->listFilter
            ->apply(
                TpProgram::query()->with('responsibleUser:id,name'),
                $data,
            )
            ->orderByDesc('id')
            ->get();

        $rows = $programs->map(function (TpProgram $p) {
            $summary = $this->presenter->programSummary($p);
            $summary['notes'] = $p->notes;

            return $summary;
        })->all();

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $this->buildFilterSummary($data),
        ]);

        $filename = 'danh-sach-chuong-trinh_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildFilterSummary(array $data): string
    {
        $parts = [];
        if (! empty($data['search'])) {
            $parts[] = 'Tìm: «'.$data['search'].'»';
        }
        if (! empty($data['status'])) {
            $parts[] = 'TT: '.$data['status'];
        }
        if (! empty($data['destination_name'])) {
            $parts[] = 'Điểm đến: '.$data['destination_name'];
        }
        if (! empty($data['school_year'])) {
            $parts[] = 'Năm học: '.$data['school_year'];
        }

        return $parts === [] ? 'Tất cả' : implode(' · ', $parts);
    }
}
