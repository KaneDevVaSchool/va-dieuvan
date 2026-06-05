<?php

namespace App\Http\Controllers\Api\TpStudent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TpImportSampleController extends Controller
{
    private const HEADERS = [
        'Họ tên',
        'Mã học sinh',
        'Khối',
        'Lớp',
        'Phụ huynh',
        'SĐT phụ huynh',
        'Địa chỉ',
    ];

    private const SAMPLE_ROWS = [
        ['Nguyễn Văn An', 'HS001', '3', '3A1', 'Nguyễn Văn Bình', '0901234567', '12 Nguyễn Huệ, Q.1, TP.HCM'],
        ['Trần Thị Bình', 'HS002', '4', '4B2', 'Trần Văn Cường', '0912345678', '45 Lê Lợi, Q.3, TP.HCM'],
        ['Lê Quốc Cường', 'HS003', '5', '5C3', 'Lê Thị Dung', '0923456789', '78 Đinh Tiên Hoàng, Q.BT, TP.HCM'],
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->can('tp_import.manage')), 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel compatibility
            fputcsv($out, self::HEADERS);
            foreach (self::SAMPLE_ROWS as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'mau-import-hoc-sinh.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
