<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DispatchRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'approved_by',
        'source_channel',
        'is_urgent',
        'trip_type',
        'origin',
        'destination',
        'depart_at',
        'arrive_by',
        'passenger_count',
        'notes',
        'status',
        'paper_status',
        'paper_received_at',
        'paper_reference',
        'rejection_reason',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_by' => 'datetime',
        'is_urgent' => 'boolean',
        'paper_received_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function trip(): HasOne
    {
        return $this->hasOne(Trip::class);
    }
}

