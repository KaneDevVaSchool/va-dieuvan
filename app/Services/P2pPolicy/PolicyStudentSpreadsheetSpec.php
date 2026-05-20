<?php

namespace App\Services\P2pPolicy;

class PolicyStudentSpreadsheetSpec
{
    public const HEADER_KEY_ROUTE_NAME = 'route_name';

    /** @var list<string> */
    public const KEYS = [
        'route_name',
        'student_code',
        'student_name',
        'class_name',
        'direction',
        'policy_type',
        'contract_number',
        'sbs_contract',
        'effective_from',
        'effective_to',
        'is_active',
        'policy_note',
    ];

    /** @var list<string> */
    public const LABELS_VI = [
        'Tên tuyến',
        'Mã học sinh',
        'Họ tên HS',
        'Lớp',
        'Chiều (one_way/two_way)',
        'Loại chính sách',
        'Số hợp đồng',
        'Hợp đồng SBS',
        'Hiệu lực từ (YYYY-MM-DD)',
        'Hiệu lực đến (YYYY-MM-DD)',
        'Active (1/0)',
        'Ghi chú',
    ];

    /**
     * @return list<array{key: string, label_vi: string, hint: string}>
     */
    public static function guideRows(): array
    {
        return [
            ['key' => 'route_name', 'label_vi' => 'Tên tuyến', 'hint' => 'Bắt buộc. Phải trùng tên tuyến đã tạo trong kỳ P2P.'],
            ['key' => 'student_code', 'label_vi' => 'Mã học sinh', 'hint' => 'Bắt buộc.'],
            ['key' => 'student_name', 'label_vi' => 'Họ tên HS', 'hint' => 'Bắt buộc.'],
            ['key' => 'class_name', 'label_vi' => 'Lớp', 'hint' => 'Tuỳ chọn.'],
            ['key' => 'direction', 'label_vi' => 'Chiều', 'hint' => 'one_way hoặc two_way. Mặc định two_way.'],
            ['key' => 'policy_type', 'label_vi' => 'Loại CS', 'hint' => 'Mặc định default.'],
            ['key' => 'contract_number', 'label_vi' => 'Số HĐ', 'hint' => 'Tuỳ chọn.'],
            ['key' => 'sbs_contract', 'label_vi' => 'SBS', 'hint' => 'Tuỳ chọn.'],
            ['key' => 'effective_from', 'label_vi' => 'Từ ngày', 'hint' => 'YYYY-MM-DD. Mặc định hôm nay.'],
            ['key' => 'effective_to', 'label_vi' => 'Đến ngày', 'hint' => 'YYYY-MM-DD hoặc để trống.'],
            ['key' => 'is_active', 'label_vi' => 'Active', 'hint' => '1 = active, 0 = ngừng. Mặc định 1.'],
            ['key' => 'policy_note', 'label_vi' => 'Ghi chú', 'hint' => 'Tuỳ chọn.'],
        ];
    }

    /**
     * @param  list<string>  $rowValues
     */
    public static function rowIsHeaderKeys(array $rowValues): bool
    {
        $first = mb_strtolower(trim($rowValues[0] ?? ''));

        return $first === self::HEADER_KEY_ROUTE_NAME;
    }
}
