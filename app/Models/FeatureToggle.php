<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureToggle extends Model
{
    protected $fillable = [
        'key',
        'name',
        'is_enabled',
        'module',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
