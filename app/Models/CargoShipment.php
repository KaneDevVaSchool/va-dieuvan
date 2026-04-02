<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

class CargoShipment extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'dispatch_request_id',
        'trip_id',
        'tracking_code',
        'sender_name',
        'receiver_name',
        'pickup_address',
        'delivery_address',
        'weight_grams',
        'quantity',
        'sla_due_at',
        'status',
        'picked_up_at',
        'delivered_at',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(DispatchRequest::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}

