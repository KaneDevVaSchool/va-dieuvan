<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchPackage extends Model
{
    protected $fillable = [
        'trip_type',
        'label',
        'total_sessions',
        'sessions_used',
        'alert_when_remaining_sessions',
        'last_low_sessions_notified_at',
    ];

    protected $casts = [
        'last_low_sessions_notified_at' => 'datetime',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(DispatchRequestTemplate::class);
    }

    /** Số buổi chưa dùng. */
    public function remainingSessions(): int
    {
        return max(0, (int) $this->total_sessions - (int) $this->sessions_used);
    }
}
