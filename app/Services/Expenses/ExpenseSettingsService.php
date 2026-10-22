<?php

namespace App\Services\Expenses;

use App\Models\ExpenseFormSetting;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Auditing\AuditLogger;
use App\Support\ChecklistAccess;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Cấu hình mẫu in hồ sơ chi phí + dữ liệu mặc định cho màn tạo hồ sơ của tài xế + xuất sổ chi phí xe.
 */
class ExpenseSettingsService
{
    private const FIELDS = [
        'company_name', 'tax_code', 'company_address', 'purchasing_place', 'city_label',
        'default_department_label', 'form_codes', 'default_signatories',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ExpenseQuery $expenses,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(): array
    {
        return ExpenseFormSetting::current()->only(['id', ...self::FIELDS]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(User $actor, array $data): array
    {
        return DB::transaction(function () use ($actor, $data) {
            $row = ExpenseFormSetting::current();
            $before = $row->only(self::FIELDS);
            $row->fill([...array_intersect_key($data, array_flip(self::FIELDS)), 'updated_by' => $actor->id])->save();

            $this->audit->log($actor->id, 'expense.settings_updated', $row, $before, $row->only(self::FIELDS));

            return $this->present();
        });
    }

    /**
     * Mặc định cho màn tạo hồ sơ của tài xế.
     *
     * @return array<string, mixed>
     */
    public function driverDefaults(User $user): array
    {
        $settings = ExpenseFormSetting::current();
        $driver = ChecklistAccess::driverFor($user);

        $myVehicleIds = $driver
            ? Vehicle::query()->where('default_driver_id', $driver->id)->pluck('id')->map(fn ($id) => (int) $id)
            : collect();

        return [
            'requester' => [
                'name' => $driver?->full_name ?? $user->name,
                'email' => $user->email ?: $driver?->email,
                'phone' => $driver?->phone ?? $user->phone,
            ],
            'department_label' => $settings->default_department_label,
            'signatories' => $settings->default_signatories ?? [],
            'vehicles' => Vehicle::query()->orderBy('license_plate')->get(['id', 'license_plate', 'type', 'status'])
                ->map(fn (Vehicle $v) => [...$v->only(['id', 'license_plate', 'type', 'status']), 'mine' => $myVehicleIds->contains((int) $v->id)])
                ->sortByDesc('mine')->values()->all(),
            'parents' => $this->expenses->parentCandidates($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $ledger  kết quả VehicleCostLedgerQuery::forVehicle
     */
    public function ledgerXlsx(array $ledger, string $from, string $to): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('So chi phi');
        $sheet->setCellValue('A1', 'SỔ CHI PHÍ XE '.($ledger['vehicle']['license_plate'] ?? ''));
        $sheet->setCellValue('A2', "Từ {$from} đến {$to}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray(['Ngày', 'Loại', 'Nội dung', 'Số tiền', 'Hóa đơn', 'Người bán', 'Trạng thái', 'Hồ sơ thanh toán', 'Chuyến', 'Người khai'], null, 'A4');
        $sheet->getStyle('A4:J4')->getFont()->setBold(true);
        $r = 5;
        foreach ($ledger['rows'] as $row) {
            $sheet->fromArray([
                $row['date'], $row['type'], $row['description'], $row['amount'],
                $row['has_invoice'] ? 'Có' : 'Không', $row['seller_name'], $row['status'] === 'confirmed' ? 'Đã duyệt' : 'Chờ duyệt',
                $row['expense_document'] ? $row['expense_document']['code'].' ('.$row['expense_document']['status'].')' : '',
                $row['trip_id'] ? '#'.$row['trip_id'] : '', $row['created_by'],
            ], null, "A{$r}");
            $r++;
        }
        $sheet->setCellValue("C{$r}", 'Tổng cộng');
        $sheet->setCellValue("D{$r}", $ledger['total']);
        $sheet->getStyle("C{$r}:D{$r}")->getFont()->setBold(true);
        $sheet->setCellValue('C'.($r + 1), 'Đã duyệt');
        $sheet->setCellValue('D'.($r + 1), $ledger['confirmed_total'] ?? 0);
        $sheet->setCellValue('C'.($r + 2), 'Chờ duyệt');
        $sheet->setCellValue('D'.($r + 2), $ledger['pending_total'] ?? 0);
        $r += 2;
        $sheet->getStyle("D5:D{$r}")->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($book))->save('php://output');

        return (string) ob_get_clean();
    }
}
