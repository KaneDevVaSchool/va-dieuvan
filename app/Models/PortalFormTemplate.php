<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalFormTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'trip_type',
        'wizard_snapshot',
    ];

    protected $casts = [
        'wizard_snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
