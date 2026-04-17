<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $hidden = [
        'wizard_snapshot',
    ];

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
        'wizard_snapshot',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'arrive_by' => 'datetime',
        'is_urgent' => 'boolean',
        'paper_received_at' => 'datetime',
        'wizard_snapshot' => 'array',
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

    public function cargoShipment(): HasOne
    {
        return $this->hasOne(CargoShipment::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
