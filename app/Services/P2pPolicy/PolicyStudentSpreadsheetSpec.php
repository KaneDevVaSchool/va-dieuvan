<?php

namespace App\Services\P2pPolicy;

class PolicyStudentSpreadsheetSpec
{
    public const HEADER_KEY_ROUTE_NAME = 'route_name';

    public const COLUMN_COUNT = 12;

    /** @var list<float> */
    public const COLUMN_WIDTHS = [
        26,  // route_name
        14,  // student_code
        24,  // student_name
        12,  // class_name
        20,  // direction
        16,  // policy_type
        18,  // contract_number
        16,  // sbs_contract
        16,  // effective_from
        16,  // effective_to
        11,  // is_active
        36,  // policy_note
    ];

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
        'Chiều đi',
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
            ['key' => 'direction', 'label_vi' => 'Chiều đi', 'hint' => 'Một chiều (sáng) hoặc hai chiều. Trong file Excel dùng mã: one_way / two_way.'],
            ['key' => 'policy_type', 'label_vi' => 'Loại chính sách', 'hint' => 'Nội bộ (internal) hoặc mặc định (default).'],
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
