<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'reconciliation_period_id',
        'status',
        'amount',
        'currency',
        'method',
        'reference',
        'executed_by',
        'executed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(ReconciliationPeriod::class, 'reconciliation_period_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}

