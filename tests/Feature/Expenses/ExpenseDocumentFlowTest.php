<?php

namespace Tests\Feature\Expenses;

use App\Models\Driver;
use App\Models\ExpenseDocument;
use App\Models\TripCost;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseDocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $driverUser;

    private Vehicle $van;

    private Vehicle $bus;

    private User $accountant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        Storage::fake('local');
        Storage::fake('compliance');
        Carbon::setTestNow(Carbon::parse('2026-09-30 03:00:00', 'UTC'));

        $this->driverUser = User::factory()->create(['is_active' => true, 'email' => 'taixe@vaschools.edu.vn']);
        $this->driverUser->assignRole('driver');
        $driver = Driver::query()->create([
            'user_id' => $this->driverUser->id,
            'full_name' => 'Trần Văn Lái',
            'phone' => '0900000002',
            'employment_status' => 'active',
            'availability_status' => 'available',
        ]);
        $this->van = Vehicle::query()->create(['license_plate' => '51M-23494', 'type' => 'Van', 'status' => 'ready', 'default_driver_id' => $driver->id]);
        $this->bus = Vehicle::query()->create(['license_plate' => '51B-51382', 'type' => 'Ford Transit', 'status' => 'ready']);

        $this->accountant = User::factory()->create(['is_active' => true]);
        $this->accountant->assignRole('admin');
    }

    private function createProposal(): array
    {
        $res = $this->actingAs($this->driverUser)->postJson('/api/driver/expenses', [
            'doc_type' => 'proposal',
            'vehicle_id' => $this->van->id,
            'purpose' => 'Bảo hiểm vật chất, TNDS bắt buộc',
            'needed_by' => '2026-10-08',
            'lines' => [
                ['vehicle_id' => $this->van->id, 'category' => 'Bảo hiểm vật chất, TNDS', 'description' => 'Thời hạn 08/09/2026 - 08/09/2027', 'unit' => 'Gói', 'quantity' => 1, 'unit_price' => 5580700],
                ['vehicle_id' => $this->bus->id, 'category' => 'Bảo hiểm vật chất, TNDS', 'unit' => 'Gói', 'quantity' => 1, 'unit_price' => 4419300],
            ],
        ]);
        $res->assertCreated();

        return $res->json('data');
    }

    public function test_driver_creates_and_submits_proposal_with_daily_code(): void
    {
        $doc = $this->createProposal();

        $this->assertSame('DX-260930-1', $doc['code']);
        $this->assertSame(10000000.0, (float) $doc['total_amount']);
        $this->assertSame('Mười triệu đồng.', $doc['amount_in_words']);
        $this->assertSame('Trần Văn Lái', $doc['requester_name']);
        $this->assertCount(2, $doc['lines']);
        $this->assertNotEmpty($doc['signatories']);
        $this->assertSame(0, TripCost::query()->count(), 'Phiếu đề xuất không tạo chi phí');

        $second = $this->postJson('/api/driver/expenses', ['doc_type' => 'proposal', 'purpose' => 'x'])->assertCreated();
        $this->assertSame('DX-260930-2', $second->json('data.code'));

        $submitted = $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']]);
        $submitted->assertOk()->assertJsonPath('data.status', 'submitted');

        $this->patchJson("/api/driver/expenses/{$doc['id']}", ['lock_version' => $submitted->json('data.lock_version'), 'purpose' => 'sửa'])
            ->assertStatus(409);

        $this->get("/api/driver/expenses/{$doc['id']}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get("/api/driver/expenses/{$doc['id']}/xlsx")->assertOk();
    }

    public function test_payment_request_links_costs_generates_statement_and_locks_costs_until_paid(): void
    {
        $proposal = $this->createProposal();

        $existing = TripCost::query()->create([
            'vehicle_id' => $this->van->id, 'type' => 'fuel', 'amount' => 500000, 'currency' => 'VND',
            'created_by' => $this->driverUser->id, 'status' => 'submitted', 'reported_on' => '2026-09-29',
        ]);

        $candidates = $this->getJson('/api/driver/expenses/cost-candidates?doc_type=payment_request&vehicle_id='.$this->van->id)->assertOk();
        $this->assertContains($existing->id, collect($candidates->json('data.items'))->pluck('id')->all());

        $res = $this->postJson('/api/driver/expenses', [
            'doc_type' => 'payment_request',
            'payment_kind' => 'reimbursement',
            'parent_id' => $proposal['id'],
            'payment_method' => 'transfer',
            'bank_account_no' => '0123456789',
            'bank_name' => 'VCB',
            'lines' => [
                ['trip_cost_id' => $existing->id],
                ['vehicle_id' => $this->van->id, 'cost_type' => 'meal', 'category' => 'Phí ăn sáng', 'quantity' => 1, 'unit_price' => 80000,
                    'purchased_on' => '2026-09-29', 'has_invoice' => false, 'seller_name' => 'Thịnh Ký', 'seller_address' => '806 Âu Cơ'],
            ],
        ]);
        $res->assertCreated();
        $doc = $res->json('data');
        $this->assertSame('HU-260930-1', $doc['code']);
        $this->assertSame($proposal['code'], $doc['basis']);
        $this->assertSame(580000.0, (float) $doc['total_amount']);

        $owned = collect($doc['lines'])->firstWhere('owns_trip_cost', true);
        $this->assertNotNull($owned['trip_cost_id']);
        $this->assertDatabaseHas('trip_costs', ['id' => $owned['trip_cost_id'], 'vehicle_id' => $this->van->id, 'has_invoice' => false, 'seller_name' => 'Thịnh Ký', 'amount' => 80000]);

        // Cùng chi phí không được đưa vào hồ sơ thanh toán thứ hai.
        $this->postJson('/api/driver/expenses', [
            'doc_type' => 'payment_request', 'payment_kind' => 'payment', 'lines' => [['trip_cost_id' => $existing->id]],
        ])->assertStatus(422);

        $submit = $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $doc['lock_version']])->assertOk();
        $statement = collect($submit->json('data.children'))->firstWhere('type_label', 'Bảng kê không hóa đơn');
        $this->assertNotNull($statement);
        $this->assertSame('BK-260930-1', $statement['code']);
        $this->assertSame(80000.0, (float) $statement['total_amount']);

        // Chi phí đã khóa theo hồ sơ đã nộp.
        $this->patchJson("/api/trip-costs/{$existing->id}", ['type' => 'fuel', 'amount' => 1])->assertStatus(409);

        // Upload scan có chữ ký → signed.
        $this->post("/api/driver/expenses/{$doc['id']}/signed-scan", ['file' => UploadedFile::fake()->create('scan.pdf', 50, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status', 'signed');

        // Kế toán trả lại → tài xế sửa được → nộp lại.
        $this->actingAs($this->accountant)->postJson("/api/expenses/{$doc['id']}/reject", ['reason' => 'Thiếu hóa đơn xăng'])
            ->assertOk()->assertJsonPath('data.status', 'rejected');

        $this->actingAs($this->driverUser);
        $fresh = $this->getJson("/api/driver/expenses/{$doc['id']}")->assertOk()->json('data');
        $this->assertTrue($fresh['can']['edit']);
        $resubmit = $this->postJson("/api/driver/expenses/{$doc['id']}/submit", ['lock_version' => $fresh['lock_version']])->assertOk();

        // Kế toán đánh dấu đã thanh toán → chi phí confirmed, bảng kê con paid.
        $this->actingAs($this->accountant)->postJson("/api/expenses/{$doc['id']}/mark-paid", [])
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertSame('confirmed', $existing->fresh()->status);
        $this->assertSame('paid', ExpenseDocument::query()->where('code', 'BK-260930-1')->value('status'));
        $this->assertNotNull($resubmit->json('data.submitted_at'));

        $this->get("/api/expenses/{$statement['id']}/xlsx")->assertOk();
        $this->get("/api/expenses/{$statement['id']}/pdf")->assertOk();
        $this->get("/api/expenses/{$doc['id']}/xlsx")->assertOk();

        // Sổ chi phí xe.
        $ledger = $this->getJson('/api/vehicle-cost-ledger?date_from=2026-09-01&date_to=2026-09-30')->assertOk();
        $row = collect($ledger->json('data.items'))->firstWhere('vehicle_id', $this->van->id);
        $this->assertSame(580000.0, (float) $row['total']);
        $this->assertSame(80000.0, (float) $row['no_invoice_total']);

        $detail = $this->getJson("/api/vehicle-cost-ledger/{$this->van->id}?date_from=2026-09-01&date_to=2026-09-30")->assertOk();
        $this->assertCount(2, $detail->json('data.rows'));
        $this->assertSame($doc['code'], $detail->json('data.rows.0.expense_document.code'));
        $this->get("/api/vehicle-cost-ledger/{$this->van->id}/export?date_from=2026-09-01&date_to=2026-09-30")->assertOk();
    }

    public function test_xlsx_fills_reference_template_cells_and_expands_rows(): void
    {
        $lines = [];
        for ($i = 1; $i <= 5; $i++) {
            $lines[] = ['vehicle_id' => $this->van->id, 'category' => "Hạng mục {$i}", 'unit' => 'Cái', 'quantity' => 1, 'unit_price' => 100000 * $i];
        }
        $doc = $this->actingAs($this->driverUser)->postJson('/api/driver/expenses', [
            'doc_type' => 'proposal', 'purpose' => 'Sửa xe', 'lines' => $lines,
        ])->assertCreated()->json('data');

        $res = $this->get("/api/driver/expenses/{$doc['id']}/xlsx")->assertOk();
        $tmp = tempnam(sys_get_temp_dir(), 'xl');
        file_put_contents($tmp, $res->getContent());
        $ws = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('Trần Văn Lái', $ws->getCell('D7')->getValue());
        $this->assertSame('Sửa xe', $ws->getCell('D13')->getValue());
        $this->assertSame('51M-23494', $ws->getCell('I24')->getValue());
        $this->assertSame('Hạng mục 1', $ws->getCell('C34')->getValue());
        $this->assertSame('Hạng mục 5', $ws->getCell('C38')->getValue());
        $this->assertSame('=SUM(I34:I38)', $ws->getCell('I39')->getValue());
        $this->assertEquals(1500000, $ws->getCell('I39')->getCalculatedValue());
        // Hàng chữ ký dịch xuống 2 dòng (49 → 51).
        $this->assertSame('Bùi Quang Toàn', $ws->getCell('B51')->getValue());
        $this->assertSame('Trần Văn Lái', $ws->getCell('I51')->getValue());
    }

    public function test_cancelling_draft_removes_costs_created_by_the_document(): void
    {
        $res = $this->actingAs($this->driverUser)->postJson('/api/driver/expenses', [
            'doc_type' => 'payment_request',
            'payment_kind' => 'payment',
            'lines' => [['vehicle_id' => $this->bus->id, 'cost_type' => 'toll', 'category' => 'Phí cầu đường', 'amount' => 120000]],
        ])->assertCreated();
        $costId = $res->json('data.lines.0.trip_cost_id');
        $this->assertDatabaseHas('trip_costs', ['id' => $costId]);

        $this->postJson('/api/driver/expenses/'.$res->json('data.id').'/cancel')->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseMissing('trip_costs', ['id' => $costId]);
    }

    public function test_submit_validates_payment_method_and_statement_fields(): void
    {
        $res = $this->actingAs($this->driverUser)->postJson('/api/driver/expenses', [
            'doc_type' => 'payment_request',
            'payment_kind' => 'advance',
            'lines' => [['vehicle_id' => $this->van->id, 'category' => 'Tạm ứng xăng', 'amount' => 1000000]],
        ])->assertCreated();
        $this->assertSame('TU-260930-1', $res->json('data.code'));
        $this->assertSame(0, TripCost::query()->count(), 'Tạm ứng không tạo chi phí');

        $this->postJson('/api/driver/expenses/'.$res->json('data.id').'/submit', ['lock_version' => $res->json('data.lock_version')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);

        $bk = $this->postJson('/api/driver/expenses', [
            'doc_type' => 'no_invoice_statement',
            'lines' => [['vehicle_id' => $this->van->id, 'category' => 'Phí ăn trưa', 'amount' => 80000]],
        ])->assertCreated();
        $this->postJson('/api/driver/expenses/'.$bk->json('data.id').'/submit', ['lock_version' => $bk->json('data.lock_version')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lines.0.seller_name', 'lines.0.purchased_on']);
    }

    public function test_access_control(): void
    {
        $doc = $this->createProposal();

        $other = User::factory()->create(['is_active' => true]);
        $other->assignRole('driver');
        $this->actingAs($other)->getJson("/api/driver/expenses/{$doc['id']}")->assertForbidden();
        $this->patchJson("/api/driver/expenses/{$doc['id']}", ['lock_version' => 0, 'purpose' => 'x'])->assertForbidden();

        $dispatcher = User::factory()->create(['is_active' => true]);
        $dispatcher->assignRole('dispatcher');
        $this->actingAs($dispatcher)->getJson("/api/expenses/{$doc['id']}")->assertOk();
        $this->postJson("/api/expenses/{$doc['id']}/mark-paid")->assertForbidden();

        $this->actingAs($this->accountant)->putJson('/api/expenses/settings', [
            'default_department_label' => 'Tổ Tài xế',
            'default_signatories' => ['proposal' => [['role' => 'Giám đốc vận hành', 'name' => 'A'], ['role' => 'Trưởng đơn vị đề xuất', 'name' => 'B']]],
        ])->assertOk()->assertJsonPath('data.default_department_label', 'Tổ Tài xế');

        $this->actingAs($this->driverUser)->getJson('/api/driver/expenses/defaults')
            ->assertOk()
            ->assertJsonPath('data.department_label', 'Tổ Tài xế')
            ->assertJsonPath('data.vehicles.0.license_plate', '51M-23494');
    }
}
