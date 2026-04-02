<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_version_id',
        'run_date',
        'trip_id',
    ];

    protected $casts = [
        'run_date' => 'date',
    ];

    public function routeVersion(): BelongsTo
    {
        return $this->belongsTo(RouteVersion::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}

