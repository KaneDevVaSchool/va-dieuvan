<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Controller;
use App\Models\TpStudent;
use App\Services\TpStudent\TpStudentListXlsxWriter;
use App\Services\TpStudent\TpStudentPresenter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TpStudentExportController extends Controller
{
    public function __construct(
        private readonly TpStudentListXlsxWriter $writer,
        private readonly TpStudentPresenter $presenter,
    ) {}

    public function download(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_student.view') || $user->can('tp_student.manage')), 403);

        $students = TpStudent::query()
            ->with(['enrollments' => function ($q) {
                $q->whereNull('unenrolled_at')
                    ->with('program:id,name,code,status,start_date')
                    ->latest('enrolled_at');
            }])
            ->tap(fn ($q) => $this->presenter->applyListFilters($q, $request))
            ->orderBy('full_name')
            ->get();

        $rows = $students->map(fn (TpStudent $s) => $this->presenter->present($s))->all();

        $tmpPath = $this->writer->writeTempFile($rows, [
            'exported_at' => now()->format('d/m/Y H:i'),
            'exported_by' => $user->name ?? $user->email,
            'total' => count($rows),
            'filter_summary' => $this->buildFilterSummary($request),
        ]);

        $filename = 'danh-sach-hoc-sinh_'.now()->format('Ymd_His').'.xlsx';

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function buildFilterSummary(Request $request): string
    {
        $parts = [];
        if ($search = trim((string) $request->query('search', ''))) {
            $parts[] = 'Tìm: «'.$search.'»';
        }
        if ($class = $request->query('class_name')) {
            $parts[] = 'Lớp: '.$class;
        }
        if ($grade = $request->query('grade')) {
            $parts[] = 'Khối: '.$grade;
        }
        if ($status = $request->query('transport_status')) {
            $parts[] = 'TT: '.$this->transportStatusLabel((string) $status);
        }
        if ($programId = $request->query('program_id')) {
            $program = \App\Models\TpProgram::query()->find($programId);
            $parts[] = 'CT: '.($program?->name ?? '#'.$programId);
        }

        return $parts === [] ? 'Tất cả' : implode(' · ', $parts);
    }

    private function transportStatusLabel(string $status): string
    {
        return match ($status) {
            'transporting' => 'Đang đưa đón',
            'pending' => 'Chờ duyệt',
            'paused' => 'Tạm dừng',
            'unregistered' => 'Chưa đăng ký',
            default => $status,
        };
    }
}
