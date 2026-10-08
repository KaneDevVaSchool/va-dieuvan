<?php

namespace App\Services\Expenses;

use App\Models\Attachment;
use App\Models\ExpenseDocument;
use App\Models\ExpenseDocumentLine;
use App\Models\User;
use App\Support\ExpenseDocumentAccess;
use App\Support\TripScheduleInstant;

class ExpensePresenter
{
    public const TYPE_LABELS = [
        'proposal' => 'Phiếu đề xuất',
        'payment_request' => 'Đề nghị thanh toán',
        'no_invoice_statement' => 'Bảng kê không hóa đơn',
    ];

    public const KIND_LABELS = [
        'payment' => 'Đề nghị thanh toán',
        'advance' => 'Đề nghị tạm ứng',
        'reimbursement' => 'Đề nghị hoàn ứng',
    ];

    public static function typeLabel(ExpenseDocument $doc): string
    {
        if ($doc->doc_type === ExpenseDocument::TYPE_PAYMENT_REQUEST) {
            return self::KIND_LABELS[$doc->payment_kind] ?? self::TYPE_LABELS['payment_request'];
        }

        return self::TYPE_LABELS[$doc->doc_type] ?? $doc->doc_type;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(ExpenseDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'code' => $doc->code,
            'doc_type' => $doc->doc_type,
            'payment_kind' => $doc->payment_kind,
            'type_label' => self::typeLabel($doc),
            'status' => $doc->status,
            'purpose' => $doc->purpose,
            'requester_name' => $doc->requester_name,
            'total_amount' => (float) $doc->total_amount,
            'proposed_on' => $doc->proposed_on?->toDateString(),
            'submitted_at' => $this->iso($doc->submitted_at),
            'paid_at' => $this->iso($doc->paid_at),
            'created_at' => $this->iso($doc->created_at),
            'lines_count' => $doc->lines_count ?? null,
            'vehicle' => $doc->relationLoaded('vehicle') && $doc->vehicle
                ? ['id' => $doc->vehicle->id, 'license_plate' => $doc->vehicle->license_plate]
                : null,
            'parent' => $doc->relationLoaded('parent') && $doc->parent
                ? ['id' => $doc->parent->id, 'code' => $doc->parent->code]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(ExpenseDocument $doc, ?User $viewer = null): array
    {
        $doc->loadMissing([
            'vehicle:id,license_plate,type',
            'parent:id,code,doc_type,payment_kind,status',
            'children:id,parent_id,code,doc_type,payment_kind,status,total_amount',
            'lines.vehicle:id,license_plate',
            'lines.tripCost:id,status,trip_id,reported_on',
            'attachments' => fn ($q) => $q->select(['id', 'attachable_type', 'attachable_id', 'kind', 'disk', 'path', 'original_name', 'size_bytes', 'mime_type', 'created_at']),
        ]);

        $owns = $viewer ? ExpenseDocumentAccess::userOwns($viewer, $doc) : false;
        $manage = $viewer ? $viewer->hasPermission('expense.manage') : false;
        $inFlight = in_array($doc->status, [ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_SIGNED], true);

        return [
            ...$this->summary($doc),
            ...$doc->only([
                'driver_id', 'requester_user_id', 'vehicle_id', 'parent_id',
                'requester_email', 'requester_phone', 'department_label', 'basis', 'is_urgent', 'urgent_reason',
                'allocation_target', 'allocation_area', 'handover_person', 'purchasing_person',
                'amount_in_words', 'payment_due_label', 'payment_method', 'bank_account_no', 'bank_name', 'bank_account_holder',
                'note', 'rejected_reason', 'lock_version',
            ]),
            'needed_by' => $doc->needed_by?->toDateString(),
            'signed_at' => $this->iso($doc->signed_at),
            'rejected_at' => $this->iso($doc->rejected_at),
            'signatories' => $doc->signatories ?? [],
            'uses_trip_costs' => $doc->usesTripCosts(),
            'children' => $doc->children->map(fn (ExpenseDocument $c) => [
                'id' => $c->id, 'code' => $c->code, 'type_label' => self::typeLabel($c), 'status' => $c->status, 'total_amount' => (float) $c->total_amount,
            ])->values()->all(),
            'lines' => $doc->lines->map(fn (ExpenseDocumentLine $l) => [
                'id' => $l->id,
                'vehicle_id' => $l->vehicle_id,
                'license_plate' => $l->vehicle?->license_plate,
                'trip_cost_id' => $l->trip_cost_id,
                'trip_cost_status' => $l->tripCost?->status,
                'owns_trip_cost' => (bool) $l->owns_trip_cost,
                'cost_type' => $l->cost_type,
                'category' => $l->category,
                'description' => $l->description,
                'unit' => $l->unit,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'amount' => (float) $l->amount,
                'purchased_on' => $l->purchased_on?->toDateString(),
                'seller_name' => $l->seller_name,
                'seller_address' => $l->seller_address,
                'has_invoice' => (bool) $l->has_invoice,
                'note' => $l->note,
            ])->values()->all(),
            'attachments' => $doc->attachments->map(fn (Attachment $a) => [
                'id' => $a->id,
                'kind' => $a->kind,
                'original_name' => $a->original_name,
                'mime_type' => $a->mime_type,
                'size_bytes' => $a->size_bytes,
                'created_at' => $this->iso($a->created_at),
            ])->values()->all(),
            'can' => [
                'edit' => $owns && $doc->isEditable(),
                'submit' => $owns && $doc->isEditable(),
                'cancel' => ($owns || $manage) && in_array($doc->status, [ExpenseDocument::STATUS_DRAFT, ExpenseDocument::STATUS_SUBMITTED, ExpenseDocument::STATUS_REJECTED], true),
                'upload_scan' => ($owns || $manage) && $inFlight,
                'reject' => $manage && $inFlight,
                'mark_paid' => $manage && $inFlight,
            ],
        ];
    }

    private function iso(mixed $value): ?string
    {
        return $value ? $value->copy()->setTimezone(TripScheduleInstant::DISPLAY_TZ)->toIso8601String() : null;
    }
}
