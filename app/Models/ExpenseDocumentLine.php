<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseDocumentLine extends Model
{
    protected $fillable = [
        'document_id',
        'sort_order',
        'vehicle_id',
        'trip_cost_id',
        'owns_trip_cost',
        'cost_type',
        'category',
        'description',
        'unit',
        'quantity',
        'unit_price',
        'amount',
        'purchased_on',
        'seller_name',
        'seller_address',
        'has_invoice',
        'note',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'owns_trip_cost' => 'boolean',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'purchased_on' => 'date:Y-m-d',
        'has_invoice' => 'boolean',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ExpenseDocument::class, 'document_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function tripCost(): BelongsTo
    {
        return $this->belongsTo(TripCost::class);
    }
}
