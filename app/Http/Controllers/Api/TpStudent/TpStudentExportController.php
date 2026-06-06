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

        $transportStatus = $request->query('transport_status');

        $students = TpStudent::query()
            ->with(['enrollments' => function ($q) {
                $q->whereNull('unenrolled_at')
                    ->with('program:id,name,code,status,start_date')
                    ->latest('enrolled_at');
            }])
            ->search($request->query('search'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('grade'), fn ($q, $g) => $q->where('grade', $g))
            ->when($request->query('class_name'), fn ($q, $c) => $q->where('class_name', $c))
            ->when($request->query('campus_id'), fn ($q, $c) => $q->where('campus_id', $c))
            ->when($request->query('program_id'), fn ($q, $p) => $q->whereHas(
                'enrollments',
                fn ($e) => $e->whereNull('unenrolled_at')->where('program_id', $p)
            ))
            ->when($transportStatus, fn ($q) => $this->presenter->applyTransportStatusFilter($q, (string) $transportStatus))
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
