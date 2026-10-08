<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cấu hình mẫu in hồ sơ chi phí (1 dòng). Giá trị mặc định lấy từ file mẫu tham khảo.
 */
class ExpenseFormSetting extends Model
{
    protected $fillable = [
        'company_name',
        'tax_code',
        'company_address',
        'purchasing_place',
        'city_label',
        'default_department_label',
        'form_codes',
        'default_signatories',
        'updated_by',
    ];

    protected $casts = [
        'form_codes' => 'array',
        'default_signatories' => 'array',
    ];

    public static function current(): self
    {
        $row = static::query()->orderBy('id')->first();

        return $row ?? static::query()->create(static::defaults());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'company_name' => 'Công ty Cổ phần Văn hóa giáo dục Việt Mỹ',
            'tax_code' => '0305072778',
            'company_address' => '252 Lạc Long Quân, Phường Bình Thới, Thành phố Hồ Chí Minh, Việt Nam',
            'purchasing_place' => 'Phòng Mua hàng',
            'city_label' => 'Tp. Hồ Chí Minh',
            'default_department_label' => 'Phòng Mua hàng',
            'form_codes' => [
                'proposal' => ['code' => 'BM.01/MH.QT.01', 'issued_on' => '', 'revision' => '01'],
                'payment_request' => ['code' => 'BM03-PKT-QT-TUTT', 'issued_on' => '', 'revision' => '01'],
                'no_invoice_statement' => ['code' => '01/TNDN', 'issued_on' => '', 'revision' => ''],
                'checklist' => ['code' => '', 'issued_on' => '', 'revision' => '01'],
            ],
            'default_signatories' => [
                'proposal' => [
                    ['role' => 'Giám đốc vận hành', 'name' => 'Bùi Quang Toàn'],
                    ['role' => 'Trưởng đơn vị đề xuất', 'name' => 'Phạm Thanh Hùng'],
                    ['role' => 'Tổ trưởng Tài xế', 'name' => 'Đỗ Mạnh Hùng'],
                    ['role' => 'Phó Tổng Giám đốc', 'name' => 'Nguyễn Ngọc Hiển'],
                ],
                'payment_request' => [
                    ['role' => 'Trưởng đơn vị', 'name' => 'Phạm Thanh Hùng'],
                    ['role' => 'P. Kế toán', 'name' => 'Trần Thị Tình'],
                    ['role' => 'Phó Tổng Giám đốc', 'name' => 'Nguyễn Ngọc Hiển'],
                ],
                'no_invoice_statement' => [
                    ['role' => 'Phó Tổng Giám đốc', 'name' => 'Nguyễn Ngọc Hiển'],
                ],
            ],
        ];
    }
}
