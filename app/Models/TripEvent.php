<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'created_by',
        'type',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

