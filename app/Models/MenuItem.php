<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;

    public const TYPE_GROUP = 'group';
    public const TYPE_ITEM = 'item';

    protected $fillable = [
        'parent_id',
        'type',
        'label',
        'label_key',
        'icon',
        'route_name',
        'url',
        'target',
        'badge_key',
        'permission_id',
        'feature_key',
        'sort_order',
        'is_visible',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
