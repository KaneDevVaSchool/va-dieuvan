<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    protected static function booted(): void
    {
        static::saved(static function () {
            Cache::forget(config('feature.cache_key'));
        });

        static::deleted(static function () {
            Cache::forget(config('feature.cache_key'));
        });
    }
}
