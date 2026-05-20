<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function originPolicyRoutes(): HasMany
    {
        return $this->hasMany(PolicyRoute::class, 'origin_campus_id');
    }

    public function destPolicyRoutes(): HasMany
    {
        return $this->hasMany(PolicyRoute::class, 'dest_campus_id');
    }
}
