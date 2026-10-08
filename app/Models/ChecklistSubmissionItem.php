<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChecklistSubmissionItem extends Model
{
    public const RESULT_OK = 'ok';

    public const RESULT_NG = 'ng';

    public const RESULT_NA = 'na';

    public const RESULTS = [self::RESULT_OK, self::RESULT_NG, self::RESULT_NA];

    /** Loại attachment cho ảnh minh chứng. */
    public const ATTACHMENT_KIND = 'checklist_evidence';

    protected $fillable = [
        'submission_id',
        'template_item_id',
        'group_name',
        'title',
        'requirement',
        'severity',
        'ng_action',
        'allow_na',
        'requires_photo_on_ng',
        'sort_order',
        'result',
        'note',
        'checked_at',
        'prefill_hint',
    ];

    protected $casts = [
        'allow_na' => 'boolean',
        'requires_photo_on_ng' => 'boolean',
        'sort_order' => 'integer',
        'checked_at' => 'datetime',
        'prefill_hint' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ChecklistSubmission::class, 'submission_id');
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplateItem::class, 'template_item_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isCritical(): bool
    {
        return $this->severity === ChecklistTemplateItem::SEVERITY_CRITICAL;
    }
}
