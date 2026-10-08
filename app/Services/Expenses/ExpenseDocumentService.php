<?php

namespace App\Services\Expenses;

use App\Models\Attachment;
use App\Models\ExpenseDocument;
use App\Models\ExpenseDocumentLine;
use App\Models\ExpenseFormSetting;
use App\Models\TripCost;
use App\Models\User;
use App\Services\Attachments\AttachmentStoreService;
use App\Services\Auditing\AuditLogger;
use App\Support\ChecklistAccess;
use App\Support\DocumentCodeGenerator;
use App\Support\ExpenseDocumentAccess;
use App\Support\FinancialDataLock;
use App\Support\TripCostAccess;
use App\Support\TripScheduleInstant;
use App\Support\VietnameseMoneyWords;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Hồ sơ chi phí xe: Phiếu đề xuất (DX), Đề nghị thanh toán / tạm ứng / hoàn ứng (TT/TU/HU), Bảng kê không hóa đơn (BK).
 *
 * Luồng: draft → submitted (chốt số liệu, in ký tay) → signed (upload scan) → paid (khóa).
 * Kế toán có thể trả lại (rejected) để tài xế sửa và nộp lại.
 *
 * Với TT/HU/BK, mỗi dòng gắn một trip_cost (sổ chi phí theo xe): chọn chi phí đã khai hoặc nhập mới
 * (hệ thống tự tạo trip_cost standalone, owns_trip_cost = true).
 */
class ExpenseDocumentService
{
    private const HEADER_FIELDS = [
        'vehicle_id', 'requester_name', 'requester_email', 'requester_phone', 'department_label',
        'purpose', 'basis', 'proposed_on', 'needed_by', 'is_urgent', 'urgent_reason',
        'allocation_target', 'allocation_area', 'handover_person', 'purchasing_person',
        'payment_due_label', 'payment_method', 'bank_account_no', 'bank_name', 'bank_account_holder', 'note',
    ];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AttachmentStoreService $files,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): ExpenseDocument
    {
        $settings = ExpenseFormSetting::current();
        $driver = ChecklistAccess::driverFor($actor);
        $parent = $this->resolveParent($actor, $data['parent_id'] ?? null, $data['doc_type'], $data['payment_kind'] ?? null);

        return DB::transaction(function () use ($actor, $data, $settings, $driver, $parent) {
            $doc = new ExpenseDocument([
                'doc_type' => $data['doc_type'],
                'payment_kind' => $data['doc_type'] === ExpenseDocument::TYPE_PAYMENT_REQUEST
                    ? ($data['payment_kind'] ?? ExpenseDocument::KIND_PAYMENT)
                    : null,
                'parent_id' => $parent?->id,
                'driver_id' => $driver?->id,
                'requester_user_id' => $actor->id,
                'requester_name' => $driver?->full_name ?? $actor->name,
                'requester_email' => $actor->email ?: $driver?->email,
                'requester_phone' => $driver?->phone ?? $actor->phone,
                'department_label' => $settings->default_department_label,
                'proposed_on' => Carbon::now(TripScheduleInstant::DISPLAY_TZ)->toDateString(),
                'purchasing_person' => $driver?->full_name ?? $actor->name,
                'signatories' => $settings->default_signatories[$data['doc_type']] ?? [],
                'status' => ExpenseDocument::STATUS_DRAFT,
                'lock_version' => 0,
            ]);

            if ($parent !== null) {
                $doc->fill([
                    'vehicle_id' => $parent->vehicle_id,
                    'purpose' => $parent->purpose,
                    'basis' => $parent->code,
                ]);
            }

            $doc->fill(array_intersect_key($data, array_flip(self::HEADER_FIELDS)));
            if (isset($data['signatories']) && is_array($data['signatories'])) {
                $doc->signatories = $this->normalizeSignatories($data['signatories']);
            }
            $doc->code = DocumentCodeGenerator::next($doc->codePrefix());
            $doc->save();

            if (array_key_exists('lines', $data)) {
                $this->syncLines($doc, $actor, $data['lines'] ?? []);
            } elseif ($parent !== null && ! $doc->usesTripCosts()) {
                $this->copyLinesFrom($parent, $doc);
            }
            $this->recalculate($doc);

            $this->audit->log($actor->id, 'expense.created', $doc, null, $doc->toArray(), ['parent_id' => $parent?->id]);

            return $doc->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ExpenseDocument $doc, User $actor, array $data): ExpenseDocument
    {
        return DB::transaction(function () use ($doc, $actor, $data) {
            $locked = $this->lockForEdit($doc, $actor, (int) $data['lock_version']);
            $before = $locked->toArray();

            $locked->fill(array_intersect_key($data, array_flip(self::HEADER_FIELDS)));
            if (isset($data['signatories']) && is_array($data['signatories'])) {
                $locked->signatories = $this->normalizeSignatories($data['signatories']);
            }
            if (array_key_exists('lines', $data)) {
                $this->syncLines($locked, $actor, $data['lines'] ?? []);
            }
            $this->recalculate($locked);
            $locked->lock_version++;
            $locked->save();

            $this->audit->log($actor->id, 'expense.updated', $locked, $before, $locked->toArray());

            return $locked->fresh();
        });
    }

    public function submit(ExpenseDocument $doc, User $actor, int $lockVersion): ExpenseDocument
    {
        return DB::transaction(function () use ($doc, $actor, $lockVersion) {
            $locked = $this->lockForEdit($doc, $actor, $lockVersion);
            $locked->load('lines');
            $this->assertSubmittable($locked);

            $before = $locked->only(['status', 'total_amount']);
            $locked->fill([
                'status' => ExpenseDocument::STATUS_SUBMITTED,
                'submitted_at' => Carbon::now(),
                'rejected_reason' => null,
            ]);
            $locked->lock_version++;
            $locked->save();

            $statement = null;
            if ($locked->doc_type === ExpenseDocument::TYPE_PAYMENT_REQUEST && $locked->usesTripCosts()) {
                $statement = $this->syncNoInvoiceStatement($locked, $actor);
            }

            $this->audit->log($actor->id, 'expense.submitted', $locked, $before, $locked->only(['status', 'total_amount', 'submitted_at']), [
                'no_invoice_statement_id' => $statement?->id,
            ]);

            return $locked->fresh();
        });
    }

    public function uploadSignedScan(ExpenseDocument $doc, User $actor, UploadedFile $file): Attachment
    {
        return DB::transaction(function () use ($doc, $actor, $file) {
            $locked = ExpenseDocument::query()->lockForUpdate()->findOrFail($doc->id);
            abort_unless(ExpenseDocumentAccess::userOwns($actor, $locked) || $actor->hasPermission('expense.manage'), 403);
            if (! in_array($locked->status, [ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_SIGNED], true)) {
                abort(409, 'Chỉ upload bản scan có chữ ký sau khi đã nộp hồ sơ.');
            }

            $attachment = $this->files->store($locked, $file, $actor->id, ExpenseDocument::ATTACHMENT_KIND_SIGNED_SCAN, 'expenses');

            $before = $locked->only(['status', 'signed_at']);
            if ($locked->status === ExpenseDocument::STATUS_SUBMITTED) {
                $locked->fill(['status' => ExpenseDocument::STATUS_SIGNED, 'signed_at' => Carbon::now()]);
                $locked->lock_version++;
                $locked->save();
            }

            $this->audit->log($actor->id, 'expense.signed_scan_uploaded', $locked, $before, $locked->only(['status', 'signed_at']), [
                'attachment_id' => $attachment->id,
            ]);

            return $attachment;
        });
    }

    public function reject(ExpenseDocument $doc, User $actor, string $reason): ExpenseDocument
    {
        return DB::transaction(function () use ($doc, $actor, $reason) {
            $locked = ExpenseDocument::query()->lockForUpdate()->findOrFail($doc->id);
            if (! in_array($locked->status, [ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_SIGNED], true)) {
                abort(409, 'Chỉ trả lại hồ sơ đã nộp / đã ký và chưa thanh toán.');
            }

            $before = $locked->only(['status', 'rejected_reason']);
            $locked->fill([
                'status' => ExpenseDocument::STATUS_REJECTED,
                'rejected_reason' => $reason,
                'rejected_by' => $actor->id,
                'rejected_at' => Carbon::now(),
            ]);
            $locked->lock_version++;
            $locked->save();

            $this->audit->log($actor->id, 'expense.rejected', $locked, $before, $locked->only(['status', 'rejected_reason', 'rejected_at']));

            return $locked->fresh();
        });
    }

    public function markPaid(ExpenseDocument $doc, User $actor, ?string $paidAt = null): ExpenseDocument
    {
        return DB::transaction(function () use ($doc, $actor, $paidAt) {
            $locked = ExpenseDocument::query()->lockForUpdate()->findOrFail($doc->id);
            if (! in_array($locked->status, [ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_SIGNED], true)) {
                abort(409, 'Chỉ đánh dấu đã thanh toán cho hồ sơ đã nộp / đã ký.');
            }

            $now = Carbon::now();
            $before = $locked->only(['status', 'paid_at']);
            $locked->fill([
                'status' => ExpenseDocument::STATUS_PAID,
                'paid_at' => $paidAt ? Carbon::parse($paidAt, TripScheduleInstant::DISPLAY_TZ) : $now,
                'paid_by' => $actor->id,
            ]);
            $locked->lock_version++;
            $locked->save();

            // Hồ sơ đã chi → xác nhận các chi phí gắn kèm (sổ chi phí xe) còn đang chờ.
            $costIds = $locked->lines()->whereNotNull('trip_cost_id')->pluck('trip_cost_id');
            $confirmed = TripCost::query()
                ->whereIn('id', $costIds)
                ->whereIn('status', ['draft', 'submitted'])
                ->update(['status' => 'confirmed', 'confirmed_by' => $actor->id, 'confirmed_at' => $now, 'updated_at' => $now]);

            // Bảng kê con đi kèm hồ sơ cũng chuyển đã thanh toán.
            $locked->children()
                ->where('doc_type', ExpenseDocument::TYPE_NO_INVOICE_STATEMENT)
                ->whereIn('status', [ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_SIGNED])
                ->update(['status' => ExpenseDocument::STATUS_PAID, 'paid_at' => $locked->paid_at, 'paid_by' => $actor->id]);

            $this->audit->log($actor->id, 'expense.paid', $locked, $before, $locked->only(['status', 'paid_at', 'paid_by']), [
                'trip_costs_confirmed' => $confirmed,
            ]);

            return $locked->fresh();
        });
    }

    public function cancel(ExpenseDocument $doc, User $actor): ExpenseDocument
    {
        return DB::transaction(function () use ($doc, $actor) {
            $locked = ExpenseDocument::query()->lockForUpdate()->findOrFail($doc->id);
            abort_unless(ExpenseDocumentAccess::userOwns($actor, $locked) || $actor->hasPermission('expense.manage'), 403);
            if (! in_array($locked->status, [ExpenseDocument::STATUS_DRAFT, ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_REJECTED], true)) {
                abort(409, 'Không thể hủy hồ sơ ở trạng thái hiện tại.');
            }

            $before = $locked->only(['status']);
            $locked->status = ExpenseDocument::STATUS_CANCELLED;
            $locked->lock_version++;
            $locked->save();

            $removedCosts = $this->deleteOwnedCosts($locked->lines()->where('owns_trip_cost', true)->get());

            $this->audit->log($actor->id, 'expense.cancelled', $locked, $before, $locked->only(['status']), [
                'owned_trip_costs_deleted' => $removedCosts,
            ]);

            return $locked->fresh();
        });
    }

    private function lockForEdit(ExpenseDocument $doc, User $actor, int $lockVersion): ExpenseDocument
    {
        $locked = ExpenseDocument::query()->lockForUpdate()->findOrFail($doc->id);
        abort_unless(ExpenseDocumentAccess::userOwns($actor, $locked), 403);
        FinancialDataLock::assertExpenseDocumentEditable($locked);

        if ((int) $locked->lock_version !== $lockVersion) {
            abort(409, 'Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại.');
        }

        return $locked;
    }

    /**
     * Đồng bộ dòng hồ sơ. Dòng mới của TT/HU/BK tạo trip_cost standalone; dòng bị bỏ → xóa trip_cost do hồ sơ tạo.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(ExpenseDocument $doc, User $actor, array $lines): void
    {
        $existing = $doc->lines()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($lines) as $i => $row) {
            $line = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if (isset($row['id']) && $line === null) {
                throw ValidationException::withMessages(["lines.{$i}.id" => 'Dòng không thuộc hồ sơ này.']);
            }
            $line ??= new ExpenseDocumentLine(['document_id' => $doc->id]);

            $values = $this->lineValues($row, $doc);
            $line->fill([...$values, 'sort_order' => ($i + 1) * 10]);

            if ($doc->usesTripCosts()) {
                $this->bindTripCost($doc, $actor, $line, $row, $i);
            } else {
                $line->trip_cost_id = null;
                $line->owns_trip_cost = false;
            }

            $line->save();
            $keptIds[] = $line->id;
        }

        $removed = $existing->except($keptIds);
        $this->deleteOwnedCosts($removed->where('owns_trip_cost', true));
        ExpenseDocumentLine::query()->whereIn('id', $removed->keys())->delete();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function lineValues(array $row, ExpenseDocument $doc): array
    {
        $qty = (float) ($row['quantity'] ?? 1) ?: 1.0;
        $unitPrice = (float) ($row['unit_price'] ?? 0);
        $amount = $unitPrice > 0 ? round($qty * $unitPrice) : round((float) ($row['amount'] ?? 0));
        if ($unitPrice <= 0 && $amount > 0) {
            $unitPrice = round($amount / $qty, 2);
        }

        $hasInvoice = $doc->doc_type === ExpenseDocument::TYPE_NO_INVOICE_STATEMENT
            ? false
            : (bool) ($row['has_invoice'] ?? true);

        return [
            'vehicle_id' => $row['vehicle_id'] ?? $doc->vehicle_id,
            'cost_type' => $row['cost_type'] ?? 'other',
            'category' => $row['category'] ?? null,
            'description' => $row['description'] ?? null,
            'unit' => $row['unit'] ?? null,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'purchased_on' => $row['purchased_on'] ?? null,
            'seller_name' => $row['seller_name'] ?? null,
            'seller_address' => $row['seller_address'] ?? null,
            'has_invoice' => $hasInvoice,
            'note' => $row['note'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function bindTripCost(ExpenseDocument $doc, User $actor, ExpenseDocumentLine $line, array $row, int $index): void
    {
        $requestedCostId = isset($row['trip_cost_id']) ? (int) $row['trip_cost_id'] : null;

        if ($requestedCostId !== null && $requestedCostId !== (int) $line->trip_cost_id) {
            $cost = TripCost::query()->with('trip')->find($requestedCostId);
            if ($cost === null || ! TripCostAccess::userCanView($actor, $cost)) {
                throw ValidationException::withMessages(["lines.{$index}.trip_cost_id" => 'Không tìm thấy chi phí đã chọn.']);
            }
            if ($cost->status === 'rejected') {
                throw ValidationException::withMessages(["lines.{$index}.trip_cost_id" => 'Chi phí đã bị từ chối — không thể đưa vào hồ sơ.']);
            }
            $this->assertCostNotHeldElsewhere($doc, $cost, $index);

            if ($line->owns_trip_cost && $line->trip_cost_id) {
                $this->deleteOwnedCosts(collect([$line]));
            }

            // Dòng lấy từ chi phí đã khai: số tiền theo chi phí gốc.
            $line->fill([
                'trip_cost_id' => $cost->id,
                'owns_trip_cost' => false,
                'vehicle_id' => $line->vehicle_id ?? $cost->vehicle_id ?? $cost->trip?->vehicle_id,
                'amount' => $cost->amount,
                'unit_price' => $cost->unit_price ?? $cost->amount,
                'quantity' => $cost->quantity ?? 1,
                'purchased_on' => $line->purchased_on ?? $cost->reported_on ?? $cost->created_at?->toDateString(),
            ]);

            return;
        }

        if ($line->trip_cost_id && ! $line->owns_trip_cost) {
            // Giữ liên kết với chi phí đã khai; số tiền theo chi phí gốc.
            $cost = TripCost::query()->find($line->trip_cost_id);
            if ($cost !== null) {
                $line->amount = $cost->amount;
            }

            return;
        }

        if ($line->vehicle_id === null) {
            throw ValidationException::withMessages(["lines.{$index}.vehicle_id" => 'Vui lòng chọn xe cho dòng chi phí.']);
        }

        $costValues = [
            'vehicle_id' => $line->vehicle_id,
            'type' => $line->cost_type ?: 'other',
            'amount' => $line->amount,
            'currency' => 'VND',
            'description' => mb_substr(trim(implode(' — ', array_filter([(string) $line->category, (string) $line->description]))), 0, 255) ?: null,
            'reported_on' => $line->purchased_on ?? Carbon::now(TripScheduleInstant::DISPLAY_TZ)->toDateString(),
            'has_invoice' => $line->has_invoice,
            'seller_name' => $line->seller_name,
            'seller_address' => $line->seller_address,
            'unit' => $line->unit,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
        ];

        if ($line->owns_trip_cost && $line->trip_cost_id) {
            TripCost::query()->whereKey($line->trip_cost_id)->update([...$costValues, 'updated_at' => now()]);

            return;
        }

        $cost = TripCost::query()->create([
            ...$costValues,
            'trip_id' => null,
            'created_by' => $actor->id,
            'status' => 'submitted',
        ]);
        $this->audit->log($actor->id, 'cost.submit', $cost, null, $cost->toArray(), ['standalone' => true, 'expense_document_id' => $doc->id]);

        $line->trip_cost_id = $cost->id;
        $line->owns_trip_cost = true;
    }

    private function assertCostNotHeldElsewhere(ExpenseDocument $doc, TripCost $cost, int $index): void
    {
        $holder = ExpenseDocumentLine::query()
            ->where('trip_cost_id', $cost->id)
            ->where('document_id', '!=', $doc->id)
            ->whereHas('document', fn ($q) => $q->where('doc_type', $doc->doc_type)->whereIn('status', ExpenseDocument::HOLDING_STATUSES))
            ->with('document:id,code')
            ->first();

        if ($holder !== null) {
            throw ValidationException::withMessages([
                "lines.{$index}.trip_cost_id" => 'Chi phí này đã nằm trong hồ sơ '.$holder->document?->code.'.',
            ]);
        }
    }

    /**
     * @param  iterable<ExpenseDocumentLine>  $lines
     */
    private function deleteOwnedCosts(iterable $lines): int
    {
        $ids = collect($lines)->pluck('trip_cost_id')->filter()->values();
        if ($ids->isEmpty()) {
            return 0;
        }

        // Chỉ xóa chi phí chưa được xác nhận và không còn nằm trong hồ sơ khác.
        $deletable = TripCost::query()
            ->whereIn('id', $ids)
            ->whereIn('status', ['draft', 'submitted'])
            ->whereDoesntHave('expenseLines', fn ($q) => $q->where('owns_trip_cost', false)
                ->whereHas('document', fn ($d) => $d->whereIn('status', ExpenseDocument::HOLDING_STATUSES)))
            ->get();

        foreach ($deletable as $cost) {
            $this->audit->log(null, 'cost.deleted_with_expense_line', $cost, $cost->toArray());
            $cost->delete();
        }

        return $deletable->count();
    }

    private function copyLinesFrom(ExpenseDocument $source, ExpenseDocument $target): void
    {
        foreach ($source->lines()->get() as $i => $line) {
            $target->lines()->create([
                ...$line->only(['vehicle_id', 'cost_type', 'category', 'description', 'unit', 'quantity', 'unit_price', 'amount', 'purchased_on', 'seller_name', 'seller_address', 'has_invoice', 'note']),
                'sort_order' => ($i + 1) * 10,
            ]);
        }
    }

    /**
     * Đề nghị thanh toán / hoàn ứng có khoản không hóa đơn → tạo / cập nhật Bảng kê con đi kèm.
     */
    private function syncNoInvoiceStatement(ExpenseDocument $doc, User $actor): ?ExpenseDocument
    {
        $noInvoice = $doc->lines()->where('has_invoice', false)->get();

        $statement = $doc->children()
            ->where('doc_type', ExpenseDocument::TYPE_NO_INVOICE_STATEMENT)
            ->whereIn('status', [ExpenseDocument::STATUS_DRAFT, ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_REJECTED])
            ->first();

        if ($noInvoice->isEmpty()) {
            if ($statement !== null) {
                $statement->update(['status' => ExpenseDocument::STATUS_CANCELLED]);
            }

            return null;
        }

        if ($statement === null) {
            $statement = new ExpenseDocument([
                'doc_type' => ExpenseDocument::TYPE_NO_INVOICE_STATEMENT,
                'parent_id' => $doc->id,
                'signatories' => ExpenseFormSetting::current()->default_signatories[ExpenseDocument::TYPE_NO_INVOICE_STATEMENT] ?? [],
                'lock_version' => 0,
            ]);
            $statement->code = DocumentCodeGenerator::next(DocumentCodeGenerator::PREFIX_NO_INVOICE);
        }

        $statement->fill([
            ...$doc->only(['driver_id', 'requester_user_id', 'vehicle_id', 'requester_name', 'requester_email', 'requester_phone', 'department_label', 'purpose', 'proposed_on', 'purchasing_person']),
            'basis' => $doc->code,
            'status' => ExpenseDocument::STATUS_SUBMITTED,
            'submitted_at' => Carbon::now(),
        ]);
        $statement->save();

        $statement->lines()->delete();
        foreach ($noInvoice->values() as $i => $line) {
            $statement->lines()->create([
                ...$line->only(['vehicle_id', 'trip_cost_id', 'cost_type', 'category', 'description', 'unit', 'quantity', 'unit_price', 'amount', 'purchased_on', 'seller_name', 'seller_address', 'note']),
                'owns_trip_cost' => false,
                'has_invoice' => false,
                'sort_order' => ($i + 1) * 10,
            ]);
        }
        $this->recalculate($statement);
        $statement->save();

        $this->audit->log($actor->id, 'expense.no_invoice_statement_synced', $statement, null, $statement->toArray(), ['parent_id' => $doc->id]);

        return $statement;
    }

    private function recalculate(ExpenseDocument $doc): void
    {
        $total = (float) $doc->lines()->sum('amount');
        if ($doc->doc_type === ExpenseDocument::TYPE_PAYMENT_REQUEST && $doc->payment_kind === ExpenseDocument::KIND_ADVANCE && $total <= 0) {
            $total = (float) $doc->total_amount;
        }
        $doc->total_amount = $total;
        $doc->amount_in_words = VietnameseMoneyWords::format($total);
        $doc->save();
    }

    private function assertSubmittable(ExpenseDocument $doc): void
    {
        $errors = [];
        if ($doc->lines->isEmpty()) {
            $errors['lines'] = 'Hồ sơ cần ít nhất một dòng chi phí.';
        }
        if ((float) $doc->total_amount <= 0) {
            $errors['total_amount'] = 'Tổng tiền phải lớn hơn 0.';
        }
        if (trim((string) $doc->requester_name) === '') {
            $errors['requester_name'] = 'Vui lòng nhập họ tên người đề nghị.';
        }
        if ($doc->doc_type === ExpenseDocument::TYPE_PROPOSAL && trim((string) $doc->purpose) === '') {
            $errors['purpose'] = 'Vui lòng nhập mục đích sử dụng.';
        }
        if ($doc->doc_type === ExpenseDocument::TYPE_PAYMENT_REQUEST) {
            if (! in_array($doc->payment_method, ['cash', 'transfer'], true)) {
                $errors['payment_method'] = 'Vui lòng chọn hình thức thanh toán.';
            }
            if ($doc->payment_method === 'transfer' && trim((string) $doc->bank_account_no) === '') {
                $errors['bank_account_no'] = 'Vui lòng nhập số tài khoản người nhận.';
            }
        }
        if ($doc->doc_type === ExpenseDocument::TYPE_NO_INVOICE_STATEMENT) {
            foreach ($doc->lines as $i => $line) {
                if (trim((string) $line->seller_name) === '') {
                    $errors["lines.{$i}.seller_name"] = 'Bảng kê cần tên người bán cho từng dòng.';
                }
                if ($line->purchased_on === null) {
                    $errors["lines.{$i}.purchased_on"] = 'Bảng kê cần ngày mua cho từng dòng.';
                }
            }
        }
        foreach ($doc->lines as $i => $line) {
            if ((float) $line->amount <= 0) {
                $errors["lines.{$i}.amount"] = 'Số tiền mỗi dòng phải lớn hơn 0.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveParent(User $actor, mixed $parentId, string $docType, ?string $kind): ?ExpenseDocument
    {
        if (empty($parentId)) {
            return null;
        }

        $parent = ExpenseDocument::query()->find((int) $parentId);
        if ($parent === null || ! ExpenseDocumentAccess::userCanView($actor, $parent)) {
            throw ValidationException::withMessages(['parent_id' => 'Không tìm thấy hồ sơ căn cứ.']);
        }
        if ($parent->status === ExpenseDocument::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['parent_id' => 'Hồ sơ căn cứ đã bị hủy.']);
        }

        $ok = match (true) {
            $docType === ExpenseDocument::TYPE_PAYMENT_REQUEST && $kind === ExpenseDocument::KIND_REIMBURSEMENT => in_array($parent->doc_type, [ExpenseDocument::TYPE_PROPOSAL, ExpenseDocument::TYPE_PAYMENT_REQUEST], true)
                && ($parent->doc_type === ExpenseDocument::TYPE_PROPOSAL || $parent->payment_kind === ExpenseDocument::KIND_ADVANCE),
            $docType === ExpenseDocument::TYPE_PAYMENT_REQUEST => $parent->doc_type === ExpenseDocument::TYPE_PROPOSAL,
            $docType === ExpenseDocument::TYPE_NO_INVOICE_STATEMENT => $parent->doc_type === ExpenseDocument::TYPE_PAYMENT_REQUEST,
            default => false,
        };
        if (! $ok) {
            throw ValidationException::withMessages(['parent_id' => 'Hồ sơ căn cứ không phù hợp với loại chứng từ.']);
        }

        return $parent;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{role: string, name: string}>
     */
    private function normalizeSignatories(array $rows): array
    {
        return collect($rows)
            ->map(fn ($r) => ['role' => mb_substr(trim((string) ($r['role'] ?? '')), 0, 120), 'name' => mb_substr(trim((string) ($r['name'] ?? '')), 0, 120)])
            ->filter(fn ($r) => $r['role'] !== '')
            ->values()
            ->all();
    }
}
