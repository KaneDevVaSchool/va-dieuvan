<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_version_id',
        'stop_order',
        'name',
        'address',
        'lat',
        'lng',
        'planned_time',
    ];

    public function routeVersion(): BelongsTo
    {
        return $this->belongsTo(RouteVersion::class);
    }
}

