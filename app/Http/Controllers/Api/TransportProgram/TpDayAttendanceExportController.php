<?php

namespace App\Http\Controllers\Api\TransportProgram;

use App\Http\Controllers\Controller;
use App\Models\TpProgramDay;
use App\Services\TransportProgram\AttendanceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TpDayAttendanceExportController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function download(Request $request, TpProgramDay $tpProgramDay): StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->isSuperAdmin() || $user->can('report.export') || $user->can('tp_attendance.manage')),
            403
        );

        $shift = $request->query('shift');
        $shiftArg = is_string($shift) ? $shift : null;
        $payload = $this->attendance->getAttendance($tpProgramDay, $shiftArg);
        $suffix = $payload['shift'] ? '-'.$payload['shift'] : '';
        $filename = 'diem-danh-'.$tpProgramDay->scheduled_date->format('Y-m-d').'-'.$tpProgramDay->id.$suffix.'.csv';

        return response()->streamDownload(function () use ($payload) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Mã HS', 'Họ tên', 'Lớp', 'Điểm đón', 'SĐT PH', 'Trạng thái', 'Loại vắng', 'Mã lý do', 'Ghi chú']);
            foreach ($payload['items'] as $row) {
                fputcsv($out, [
                    $row['code'],
                    $row['full_name'],
                    $row['class_name'],
                    $row['pickup_point'],
                    $row['parent_phone'],
                    $row['display_status'],
                    $row['category'],
                    $row['reason_code'],
                    $row['absence_reason'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
