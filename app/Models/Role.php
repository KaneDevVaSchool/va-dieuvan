<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'description',
        'color',
        'sort_order',
        'status',
        'is_system',
        'parent_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Users who have this role assigned via model_has_roles. */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key') ?? 'role_id',
            config('permission.column_names.model_morph_key') ?? 'model_id',
        );
    }
}
