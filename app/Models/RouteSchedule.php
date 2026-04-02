<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_version_id',
        'day_of_week',
        'depart_time',
        'arrive_time',
    ];

    public function routeVersion(): BelongsTo
    {
        return $this->belongsTo(RouteVersion::class);
    }
}

