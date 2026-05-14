<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchRequestTemplate extends Model
{
    protected $fillable = [
        'requester_id',
        'dispatch_package_id',
        'is_active',
        'trip_type',
        'origin',
        'destination',
        'passenger_count',
        'notes',
        'arrive_offset_minutes',
        'recurrence_rule',
        'recurrence_end_date',
        'recurrence_time',
        'wizard_snapshot',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'recurrence_end_date' => 'date',
        'recurrence_rule' => 'array',
        'wizard_snapshot' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function dispatchPackage(): BelongsTo
    {
        return $this->belongsTo(DispatchPackage::class);
    }

    public function dispatchRequests(): HasMany
    {
        return $this->hasMany(DispatchRequest::class, 'dispatch_request_template_id');
    }
}
