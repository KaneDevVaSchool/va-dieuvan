<?php

namespace Tests\Feature\Console;

use App\Models\Driver;
use App\Models\DriverComplianceDocument;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleComplianceDocument;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportFleetHandoverCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'fleet').'.xlsx';
        $this->writeWorkbook($this->path);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    /** Mô phỏng file bàn giao: tên sheet NFD, header 2 tầng có merge ở sheet xe. */
    private function writeWorkbook(string $path): void
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);

        $drivers = $book->createSheet();
        $drivers->setTitle(\Normalizer::normalize('TT Tài xế', \Normalizer::FORM_D));
        $drivers->fromArray([
            [null, 'STT', 'Họ và tên', 'Mã NV', 'SĐT', 'Số CCCD', 'Số GPLX', 'Ngày hết hạn GPLX', 'Hạng GPLX', 'Ngày sinh', 'Giấy tờ tùy thân'],
            [null, 1, 'Đỗ Mạnh Hùng', 'VA010006', '0938220438', '015074000156', '790969001304', '07/06/2028', 'D', '24/11/1974', 'https://drive/hung'],
            [null, 2, 'Dương Hoàng Trọng', 'VA010502', '0938220438', '084085009207', '790127794828', '19/09/2026', 'B2', '27/02/1985', ''],
        ], null, 'A2', true);

        $vehicles = $book->createSheet();
        $vehicles->setTitle('TT xe nội bộ');
        $vehicles->fromArray([
            [null, 'STT', 'BKS', 'Loại xe', 'Tên chủ xe', 'Số khung / Số máy', 'Năm sản xuất', 'Năm mua xe', 'Niên hạn sử dụng',
                'Số chỗ/ trọng tải', 'Bảo hiểm', 'Thời hạn bảo hiểm', 'ĐĂNG KIỂM', null, 'BẢO DƯỠNG', null, 'Cà vẹt xe',
                "Bảo hiểm xe\n(update 2026.08.06)", 'Chứng nhận kiểm định', 'Hợp đồng thuê xe', 'Ghi chú', 'Thông tin Tài xế'],
            [null, null, null, null, null, null, null, null, null, null, null, null, 'Hạn đăng kiểm', 'Thời gian ĐK',
                'Lần BTBD gần nhất', 'Bảo dưỡng', null, null, null, null, null, 'Tài xế phụ trách', 'SĐT TX'],
            [null, 1, '51a 796.68', 'TOYOTA INNOVA - Nâu vàng', 'Cty Hoàng Việt', "SK: RL4\nSM: 1TR", '2014', '2/2014', 'Không có niên hạn',
                '8', 'MIC', '01/03/2027', '10/06/2027', 'Đăng kiểm 12 tháng/lần', '28/02/2026', '5000km', "https://drive/cv1\nhttps://drive/cv2",
                'https://drive/bh', '', '', '', 'Đỗ Mạnh Hùng', '0983931404'],
            [null, 2, '51B 149.84', 'TOYOTA HIACE - Bạc', 'THCS Việt Mỹ', '', '2014', '11/2014', '2034',
                '16', 'MIC', '17g27 ngày 17/11/2025 – 17g27 ngày 17/11/2026', '04/08/2026', '', '', '', '', '', '', '', 'Xe bàn giao Vũng Tàu', '', ''],
        ], null, 'A3', true);
        $vehicles->mergeCells('M3:N3');
        $vehicles->mergeCells('O3:P3');

        (new Xlsx($book))->save($path);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->seed(RbacSeeder::class);

        $exit = Artisan::call('fleet:import-handover', ['file' => $this->path, '--dry-run' => true]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame(0, Driver::query()->count());
        $this->assertSame(0, Vehicle::query()->count());
    }

    public function test_imports_drivers_vehicles_documents_and_links_user_by_employee_code(): void
    {
        $this->seed(RbacSeeder::class);
        $hungUser = User::factory()->create(['name' => 'Hung CMS', 'employee_code' => 'VA010006']);

        $exit = Artisan::call('fleet:import-handover', ['file' => $this->path]);
        $this->assertSame(0, $exit, Artisan::output());

        $hung = Driver::query()->where('national_id', '015074000156')->firstOrFail();
        $this->assertSame('Đỗ Mạnh Hùng', $hung->full_name);
        $this->assertSame($hungUser->id, $hung->user_id);
        $this->assertTrue($hungUser->fresh()->hasRole('driver'));
        $this->assertSame('2028-06-07', $hung->license_expires_at->toDateString());

        $license = DriverComplianceDocument::query()->where('driver_id', $hung->id)->where('doc_type', 'license')->firstOrFail();
        $this->assertStringContainsString('790969001304', $license->title);
        $idCard = DriverComplianceDocument::query()->where('driver_id', $hung->id)->where('doc_type', 'id_card')->firstOrFail();
        $this->assertStringContainsString('Mã NV: VA010006', $idCard->notes);
        $this->assertStringContainsString('24/11/1974', $idCard->notes);

        $trong = Driver::query()->where('national_id', '084085009207')->firstOrFail();
        $this->assertNull($trong->user_id);

        // --link gán tay khi tên trên tài khoản khác tên trong file.
        $trongUser = User::factory()->create(['name' => 'Duong H. Trong', 'email' => 'trong@hcm.example.com']);
        $this->assertSame(0, Artisan::call('fleet:import-handover', [
            'file' => $this->path,
            '--link' => ['VA010502=Trong@hcm.example.com'],
        ]));
        $this->assertSame($trongUser->id, $trong->fresh()->user_id);
        $this->assertTrue($trongUser->fresh()->hasRole('driver'));

        $innova = Vehicle::query()->where('license_plate', '51A 796.68')->firstOrFail();
        $this->assertSame('minibus', $innova->type);
        $this->assertSame(8, $innova->seat_count);
        $this->assertSame(2014, $innova->year_manufactured);
        $this->assertSame('2014-02-01', $innova->purchased_at->toDateString());
        $this->assertNull($innova->usage_expires_year);
        $this->assertSame('2027-03-01', $innova->insurance_expires_at->toDateString());
        $this->assertSame('2027-06-10', $innova->inspection_expires_at->toDateString());
        $this->assertSame('2026-02-28', $innova->last_maintenance_at->toDateString());
        $this->assertSame('5000km', $innova->maintenance_schedule_note);
        $this->assertSame($hung->id, $innova->default_driver_id);
        $this->assertSame('ready', $innova->status);
        $this->assertStringContainsString('TOYOTA INNOVA', $innova->notes);
        $this->assertSame(
            "https://drive/cv1\nhttps://drive/cv2",
            VehicleComplianceDocument::query()->where('vehicle_id', $innova->id)->where('doc_type', 'registration')->value('notes'),
        );

        $hiace = Vehicle::query()->where('license_plate', '51B 149.84')->firstOrFail();
        $this->assertSame('2026-11-17', $hiace->insurance_expires_at->toDateString());
        $this->assertStringContainsString('17g27', $hiace->insurance_policy_note);
        $this->assertSame(2034, $hiace->usage_expires_year);

        // Chạy lại: cập nhật, không nhân bản.
        $this->assertSame(0, Artisan::call('fleet:import-handover', ['file' => $this->path]));
        $this->assertSame(2, Driver::query()->count());
        $this->assertSame(2, Vehicle::query()->count());
        $this->assertSame(2, DriverComplianceDocument::query()->where('driver_id', $hung->id)->count());
    }
}
