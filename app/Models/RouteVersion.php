<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'version',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'metadata',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(RouteSchedule::class);
    }
}

