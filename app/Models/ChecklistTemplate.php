<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChecklistTemplate extends Model
{
    use SoftDeletes;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCIES = [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY];

    public const CODE_DAILY = 'daily_safety';

    public const CODE_WEEKLY = 'weekly_compliance';

    protected $fillable = [
        'code',
        'name',
        'frequency',
        'version',
        'is_active',
        'form_meta',
    ];

    protected $casts = [
        'form_meta' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class, 'template_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public function reminderSlots(): HasMany
    {
        return $this->hasMany(ChecklistReminderSlot::class, 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
