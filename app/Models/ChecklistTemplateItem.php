<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistTemplateItem extends Model
{
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_NON_CRITICAL = 'non_critical';

    public const SEVERITIES = [self::SEVERITY_CRITICAL, self::SEVERITY_NON_CRITICAL];

    protected $fillable = [
        'template_id',
        'group_name',
        'sort_order',
        'title',
        'requirement',
        'severity',
        'ng_action',
        'allow_na',
        'requires_photo_on_ng',
        'auto_source',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'allow_na' => 'boolean',
        'requires_photo_on_ng' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }
}
