<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseDocument extends Model
{
    use SoftDeletes;

    public const TYPE_PROPOSAL = 'proposal';

    public const TYPE_PAYMENT_REQUEST = 'payment_request';

    public const TYPE_NO_INVOICE_STATEMENT = 'no_invoice_statement';

    public const TYPES = [self::TYPE_PROPOSAL, self::TYPE_PAYMENT_REQUEST, self::TYPE_NO_INVOICE_STATEMENT];

    public const KIND_PAYMENT = 'payment';

    public const KIND_ADVANCE = 'advance';

    public const KIND_REIMBURSEMENT = 'reimbursement';

    public const PAYMENT_KINDS = [self::KIND_PAYMENT, self::KIND_ADVANCE, self::KIND_REIMBURSEMENT];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_SIGNED = 'signed';

    public const STATUS_PAID = 'paid';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_SIGNED,
        self::STATUS_PAID,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /** Trạng thái đang "giữ" dòng trip_cost (một chi phí không được nằm trong 2 hồ sơ thanh toán cùng lúc). */
    public const HOLDING_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_SIGNED,
        self::STATUS_PAID,
        self::STATUS_REJECTED,
    ];

    /** Trạng thái đã chốt số liệu — khóa các trip_cost liên kết. */
    public const LOCKING_STATUSES = [self::STATUS_SUBMITTED, self::STATUS_SIGNED, self::STATUS_PAID];

    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_REJECTED];

    /** Loại attachment cho bản scan có chữ ký. */
    public const ATTACHMENT_KIND_SIGNED_SCAN = 'signed_scan';

    protected $fillable = [
        'code',
        'doc_type',
        'payment_kind',
        'parent_id',
        'driver_id',
        'requester_user_id',
        'vehicle_id',
        'requester_name',
        'requester_email',
        'requester_phone',
        'department_label',
        'purpose',
        'basis',
        'proposed_on',
        'needed_by',
        'is_urgent',
        'urgent_reason',
        'allocation_target',
        'allocation_area',
        'handover_person',
        'purchasing_person',
        'total_amount',
        'amount_in_words',
        'payment_due_label',
        'payment_method',
        'bank_account_no',
        'bank_name',
        'bank_account_holder',
        'note',
        'signatories',
        'status',
        'submitted_at',
        'signed_at',
        'paid_at',
        'paid_by',
        'rejected_reason',
        'rejected_by',
        'rejected_at',
        'lock_version',
    ];

    protected $casts = [
        'proposed_on' => 'date:Y-m-d',
        'needed_by' => 'date:Y-m-d',
        'is_urgent' => 'boolean',
        'total_amount' => 'decimal:2',
        'signatories' => 'array',
        'submitted_at' => 'datetime',
        'signed_at' => 'datetime',
        'paid_at' => 'datetime',
        'rejected_at' => 'datetime',
        'lock_version' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseDocumentLine::class, 'document_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }

    public function isManagedStatement(): bool
    {
        return $this->doc_type === self::TYPE_NO_INVOICE_STATEMENT && $this->parent_id
            && ($this->relationLoaded('parent') ? $this->parent?->doc_type : $this->parent()->value('doc_type')) === self::TYPE_PAYMENT_REQUEST;
    }

    /**
     * Chứng từ ghi nhận chi phí đã phát sinh (TT, HU, BK) → dòng gắn với trip_costs (sổ chi phí xe).
     * Phiếu đề xuất (DX) và Đề nghị tạm ứng (TU) là số dự kiến, không tạo chi phí.
     */
    public function usesTripCosts(): bool
    {
        if ($this->doc_type === self::TYPE_NO_INVOICE_STATEMENT) {
            return true;
        }

        return $this->doc_type === self::TYPE_PAYMENT_REQUEST && $this->payment_kind !== self::KIND_ADVANCE;
    }

    public function codePrefix(): string
    {
        return match (true) {
            $this->doc_type === self::TYPE_PROPOSAL => \App\Support\DocumentCodeGenerator::PREFIX_PROPOSAL,
            $this->doc_type === self::TYPE_NO_INVOICE_STATEMENT => \App\Support\DocumentCodeGenerator::PREFIX_NO_INVOICE,
            $this->payment_kind === self::KIND_ADVANCE => \App\Support\DocumentCodeGenerator::PREFIX_ADVANCE,
            $this->payment_kind === self::KIND_REIMBURSEMENT => \App\Support\DocumentCodeGenerator::PREFIX_REIMBURSEMENT,
            default => \App\Support\DocumentCodeGenerator::PREFIX_PAYMENT,
        };
    }
}
