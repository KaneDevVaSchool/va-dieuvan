<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistReminderSlot extends Model
{
    protected $fillable = [
        'template_id',
        'remind_at',
        'weekdays',
        'escalate_after_minutes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'weekdays' => 'array',
        'escalate_after_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }
}
