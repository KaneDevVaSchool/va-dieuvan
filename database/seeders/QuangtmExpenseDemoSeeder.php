<?php

namespace Database\Seeders;

use App\Models\DispatchRequest;
use App\Models\Driver;
use App\Models\ExpenseDocument;
use App\Models\Trip;
use App\Models\TripCost;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Auditing\AuditLogger;
use App\Services\Expenses\ExpenseDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Local only: hồ sơ chi phí mẫu cho tài xế quangtm (DX / TT / TU / HU / BK ở các trạng thái khác nhau).
 * Chạy sau QuangtmLocalDemoSeeder. Đã seed (có hồ sơ mang marker) thì bỏ qua toàn bộ.
 */
class QuangtmExpenseDemoSeeder extends Seeder
{
    private const MARKER = '[LOCAL DEMO QUANGTM]';

    public function run(): void
    {
        $connection = config('database.default');
        $host = config('database.connections.'.$connection.'.host');
        if (! app()->environment(['local', 'dev', 'development'])
            || $connection !== 'mysql'
            || ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || config('database.connections.'.$connection.'.url')) {
            throw new RuntimeException('This seeder requires a local development MySQL database.');
        }

        $user = User::where('email', 'quangtm@hcm.vaschools.edu.vn')->firstOrFail();
        $driver = Driver::where('user_id', $user->id)->first();
        $vehicle = Vehicle::where('license_plate', '51B-999.01')->first();
        if ($driver === null || $vehicle === null) {
            throw new RuntimeException('Run QuangtmLocalDemoSeeder first (driver + demo vehicle 51B-999.01).');
        }

        $approver = User::where('email', 'phongcongnghe@vaschools.edu.vn')->first() ?? $user;
        $local = Carbon::now('Asia/Ho_Chi_Minh');
        $day = fn (int $ago) => $local->copy()->subDays($ago)->toDateString();

        $this->seedRejectedCosts($user, $vehicle, $approver, $day);

        if (ExpenseDocument::withTrashed()->where('requester_user_id', $user->id)->where('note', 'like', self::MARKER.'%')->exists()) {
            $this->command?->warn('Hồ sơ chi phí mẫu của quangtm đã tồn tại — bỏ qua.');

            return;
        }

        $service = app(ExpenseDocumentService::class);
        $completedTrip = Trip::query()
            ->where('driver_id', $driver->id)->where('status', 'completed')
            ->whereIn('dispatch_request_id', DispatchRequest::where('notes', 'like', self::MARKER.'%')->select('id'))
            ->latest('id')->first();

        Model::withoutEvents(function () use ($user, $vehicle, $approver, $service, $day, $completedTrip) {
            DB::transaction(function () use ($user, $vehicle, $approver, $service, $day, $completedTrip) {
                $header = fn (string $key, array $extra) => [
                    'vehicle_id' => $vehicle->id,
                    'requester_phone' => $user->phone ?: '0909000111',
                    'note' => self::MARKER.' '.$key,
                    ...$extra,
                ];
                $submit = fn (ExpenseDocument $doc) => $service->submit($doc, $user, (int) $doc->lock_version);

                // Chi phí tài xế đã khai, chưa vào hồ sơ — dùng khi demo "Chi phí đã khai".
                $declared = [
                    ['trip_id' => $completedTrip?->id, 'type' => 'fuel', 'amount' => 650000, 'description' => 'Đổ dầu chuyến công tác Văn phòng VAS',
                        'has_invoice' => true, 'invoice_no' => '0012345', 'seller_name' => 'Cửa hàng xăng dầu Petrolimex số 12', 'unit' => 'lít', 'quantity' => 30, 'unit_price' => 21666.67, 'reported_on' => $day(1)],
                    ['trip_id' => $completedTrip?->id, 'type' => 'toll', 'amount' => 120000, 'description' => 'Phí cao tốc TP.HCM – Long Thành',
                        'has_invoice' => true, 'seller_name' => 'VEC', 'unit' => 'lượt', 'quantity' => 2, 'unit_price' => 60000, 'reported_on' => $day(1)],
                    ['trip_id' => null, 'type' => 'parking', 'amount' => 40000, 'description' => 'Gửi xe bãi Bệnh viện Quận 7',
                        'has_invoice' => false, 'seller_name' => 'Bãi giữ xe Phú Mỹ', 'seller_address' => 'Nguyễn Lương Bằng, Quận 7', 'unit' => 'lượt', 'quantity' => 1, 'unit_price' => 40000, 'reported_on' => $day(2)],
                ];
                $declaredCosts = [];
                foreach ($declared as $row) {
                    $cost = TripCost::create([...$row, 'vehicle_id' => $vehicle->id, 'created_by' => $user->id, 'currency' => 'VND', 'status' => 'submitted']);
                    app(AuditLogger::class)->log($user->id, 'cost.submit', $cost, null, $cost->toArray(), ['seeder' => self::class]);
                    $declaredCosts[] = $cost;
                }

                // 1) Đề xuất bảo dưỡng → đã nộp, làm căn cứ cho Đề nghị thanh toán đã thanh toán.
                $proposal = $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PROPOSAL,
                    ...$header('proposal-maintenance', [
                        'purpose' => 'Bảo dưỡng định kỳ mốc 40.000 km cho xe 51B-999.01',
                        'basis' => 'Lịch bảo dưỡng định kỳ của nhà sản xuất',
                        'proposed_on' => $day(12), 'needed_by' => $day(9),
                        'handover_person' => $user->name,
                    ]),
                    'lines' => [
                        ['cost_type' => 'maintenance', 'category' => 'Vật tư', 'description' => 'Thay nhớt động cơ', 'unit' => 'lít', 'quantity' => 8, 'unit_price' => 150000],
                        ['cost_type' => 'maintenance', 'category' => 'Phụ tùng', 'description' => 'Thay lọc gió động cơ', 'unit' => 'cái', 'quantity' => 1, 'unit_price' => 350000],
                        ['cost_type' => 'maintenance', 'category' => 'Dịch vụ', 'description' => 'Rửa xe, vệ sinh khoang máy', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 100000],
                    ],
                ]);
                $proposal = $submit($proposal);

                // 2) Đề nghị thanh toán theo đề xuất → có dòng không hóa đơn (tự sinh BK) → đã ký → đã thanh toán.
                $paid = $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PAYMENT_REQUEST, 'payment_kind' => ExpenseDocument::KIND_PAYMENT,
                    'parent_id' => $proposal->id,
                    ...$header('payment-maintenance', [
                        'proposed_on' => $day(8), 'payment_due_label' => 'Trong tháng',
                        'payment_method' => 'cash',
                    ]),
                    'lines' => [
                        ['cost_type' => 'maintenance', 'category' => 'Vật tư', 'description' => 'Thay nhớt động cơ', 'unit' => 'lít', 'quantity' => 8, 'unit_price' => 145000,
                            'has_invoice' => true, 'purchased_on' => $day(9), 'seller_name' => 'Garage Toyota Phú Mỹ Hưng'],
                        ['cost_type' => 'maintenance', 'category' => 'Phụ tùng', 'description' => 'Thay lọc gió động cơ', 'unit' => 'cái', 'quantity' => 1, 'unit_price' => 350000,
                            'has_invoice' => true, 'purchased_on' => $day(9), 'seller_name' => 'Garage Toyota Phú Mỹ Hưng'],
                        ['cost_type' => 'maintenance', 'category' => 'Dịch vụ', 'description' => 'Rửa xe, vệ sinh khoang máy', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 80000,
                            'has_invoice' => false, 'purchased_on' => $day(9), 'seller_name' => 'Tiệm rửa xe Anh Tuấn', 'seller_address' => '123 Nguyễn Thị Thập, Quận 7'],
                    ],
                ]);
                $paid = $submit($paid);
                $this->uploadScan($service, $paid, $user);
                $service->markPaid($paid->fresh(), $approver, $day(5).' 10:00:00');

                // 3) Đề nghị thanh toán chuyển khoản, có dòng lấy từ chi phí đã khai → đã ký, chờ kế toán.
                $signed = $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PAYMENT_REQUEST, 'payment_kind' => ExpenseDocument::KIND_PAYMENT,
                    ...$header('payment-fuel', [
                        'proposed_on' => $day(3), 'payment_due_label' => 'Trước ngày 30',
                        'payment_method' => 'transfer', 'bank_account_no' => '0071000123456', 'bank_name' => 'Vietcombank',
                        'bank_account_holder' => mb_strtoupper($user->name),
                    ]),
                    'lines' => [
                        ['cost_type' => 'fuel', 'category' => 'Nhiên liệu', 'description' => 'Đổ dầu đưa đón học sinh tuần 2', 'unit' => 'lít', 'quantity' => 60, 'unit_price' => 21500,
                            'has_invoice' => true, 'purchased_on' => $day(4), 'seller_name' => 'Cửa hàng xăng dầu Petrolimex số 12'],
                        ['cost_type' => 'parking', 'category' => 'Phí bến bãi', 'description' => 'Gửi xe chờ đón học sinh', 'unit' => 'lượt', 'quantity' => 3, 'unit_price' => 20000,
                            'has_invoice' => false, 'purchased_on' => $day(4), 'seller_name' => 'Bãi giữ xe Crescent Mall', 'seller_address' => 'Tôn Dật Tiên, Quận 7'],
                    ],
                ]);
                $signed = $submit($signed);
                $this->uploadScan($service, $signed, $user);

                // 4) Tạm ứng công tác → đã nộp (chưa tạo chi phí thực tế).
                $advance = $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PAYMENT_REQUEST, 'payment_kind' => ExpenseDocument::KIND_ADVANCE,
                    ...$header('advance-trip', [
                        'purpose' => 'Tạm ứng chi phí chuyến đưa đoàn giáo viên đi Vũng Tàu',
                        'proposed_on' => $day(2), 'payment_due_label' => 'Trước chuyến đi',
                        'payment_method' => 'cash',
                    ]),
                    'lines' => [
                        ['cost_type' => 'fuel', 'category' => 'Nhiên liệu', 'description' => 'Dầu khứ hồi TP.HCM – Vũng Tàu', 'unit' => 'lít', 'quantity' => 70, 'unit_price' => 21500],
                        ['cost_type' => 'toll', 'category' => 'Phí cầu đường', 'description' => 'Phí cao tốc khứ hồi', 'unit' => 'lượt', 'quantity' => 2, 'unit_price' => 180000],
                        ['cost_type' => 'meal', 'category' => 'Công tác phí', 'description' => 'Ăn uống tài xế', 'unit' => 'ngày', 'quantity' => 2, 'unit_price' => 150000],
                    ],
                ]);
                $submit($advance);

                // 5) Đề nghị thanh toán sửa chữa → kế toán trả lại, tài xế cần bổ sung.
                $rejected = $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PAYMENT_REQUEST, 'payment_kind' => ExpenseDocument::KIND_PAYMENT,
                    ...$header('payment-repair', [
                        'proposed_on' => $day(6), 'payment_method' => 'cash',
                    ]),
                    'lines' => [
                        ['cost_type' => 'repair', 'category' => 'Sửa chữa', 'description' => 'Thay bóng đèn pha bên trái', 'unit' => 'cái', 'quantity' => 1, 'unit_price' => 450000,
                            'has_invoice' => true, 'purchased_on' => $day(6), 'seller_name' => 'Điện ô tô Minh Phát'],
                    ],
                ]);
                $rejected = $submit($rejected);
                $service->reject($rejected, $approver, 'Bổ sung ảnh hóa đơn đỏ và ghi rõ số hóa đơn cho khoản thay đèn pha.');

                // 6) Đề xuất thay lốp còn nháp — để demo sửa / nộp / lập TT từ phiếu trực tiếp.
                $service->create($user, [
                    'doc_type' => ExpenseDocument::TYPE_PROPOSAL,
                    ...$header('proposal-tires', [
                        'purpose' => 'Thay 2 lốp trước do mòn quá vạch an toàn (phát hiện khi làm checklist)',
                        'basis' => 'Checklist Daily ghi nhận lốp trước mòn',
                        'proposed_on' => $day(0), 'needed_by' => $day(-3),
                        'is_urgent' => true, 'urgent_reason' => 'Ảnh hưởng an toàn khi đưa đón học sinh',
                    ]),
                    'lines' => [
                        ['cost_type' => 'repair', 'category' => 'Phụ tùng', 'description' => 'Lốp Michelin 215/70R15', 'unit' => 'cái', 'quantity' => 2, 'unit_price' => 2800000],
                        ['cost_type' => 'repair', 'category' => 'Dịch vụ', 'description' => 'Công thay, cân bằng động', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 200000],
                    ],
                ]);

                foreach (ExpenseDocument::where('requester_user_id', $user->id)->orderBy('id')->get() as $doc) {
                    $this->command?->line(sprintf('%-16s %-22s %-10s %12s đ', $doc->code, $doc->doc_type.($doc->payment_kind ? '/'.$doc->payment_kind : ''), $doc->status, number_format((float) $doc->total_amount, 0, ',', '.')));
                }
                $this->command?->info(count($declaredCosts).' chi phí đã khai còn trống để demo chọn vào hồ sơ.');
            });
        });
    }

    /** Khoản chi tài xế đã khai nhưng điều phối từ chối — không vào sổ, không chọn được vào hồ sơ. */
    private function seedRejectedCosts(User $user, Vehicle $vehicle, User $approver, \Closure $day): void
    {
        $rows = [
            ['type' => 'other', 'amount' => 150000, 'description' => 'Rửa xe cá nhân cuối tuần', 'reported_on' => $day(5),
                'has_invoice' => false, 'seller_name' => 'Tiệm rửa xe Anh Tuấn', 'unit' => 'lần', 'quantity' => 1, 'unit_price' => 150000,
                'rejection_reason' => 'Rửa xe ngoài lịch công tác, không thuộc chi phí của đơn vị.'],
            ['type' => 'meal', 'amount' => 350000, 'description' => 'Ăn trưa tiếp khách tại nhà hàng', 'reported_on' => $day(3),
                'has_invoice' => false, 'seller_name' => 'Nhà hàng Hương Việt', 'unit' => 'bữa', 'quantity' => 1, 'unit_price' => 350000,
                'rejection_reason' => 'Chi phí tiếp khách không thuộc định mức của tài xế, cần phê duyệt trước.'],
        ];

        Model::withoutEvents(fn () => DB::transaction(function () use ($rows, $user, $vehicle, $approver) {
            $audit = app(AuditLogger::class);
            foreach ($rows as $row) {
                $exists = TripCost::where('created_by', $user->id)->where('status', 'rejected')
                    ->where('description', $row['description'])->exists();
                if ($exists) {
                    continue;
                }
                $reason = $row['rejection_reason'];
                unset($row['rejection_reason']);
                $cost = TripCost::create([...$row, 'trip_id' => null, 'vehicle_id' => $vehicle->id, 'created_by' => $user->id, 'currency' => 'VND', 'status' => 'submitted']);
                $audit->log($user->id, 'cost.submit', $cost, null, $cost->toArray(), ['seeder' => self::class]);
                $before = $cost->toArray();
                $cost->update(['status' => 'rejected', 'confirmed_by' => $approver->id, 'confirmed_at' => now(), 'rejection_reason' => $reason]);
                $audit->log($approver->id, 'cost.reject', $cost, $before, $cost->toArray(), ['reason' => $reason]);
                $this->command?->line("Chi phí bị từ chối #{$cost->id}: {$cost->description}");
            }
        }));
    }

    private function uploadScan(ExpenseDocumentService $service, ExpenseDocument $doc, User $actor): void
    {
        $path = tempnam(sys_get_temp_dir(), 'scan');
        file_put_contents($path, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
        try {
            $service->uploadSignedScan($doc, $actor, new UploadedFile($path, "ban-ky-{$doc->code}.pdf", 'application/pdf', null, true));
        } finally {
            @unlink($path);
        }
    }
}
