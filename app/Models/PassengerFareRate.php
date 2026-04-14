<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PassengerFareRate extends Model
{
    protected $fillable = [
        'sort_order',
        'package_code',
        'package_label',
        'seat_7',
        'seat_15',
        'seat_28',
        'seat_33',
        'seat_45',
        'limo_9',
        'limo_11',
        'driver_self_support',
    ];

    protected $casts = [
        'seat_7' => 'decimal:2',
        'seat_15' => 'decimal:2',
        'seat_28' => 'decimal:2',
        'seat_33' => 'decimal:2',
        'seat_45' => 'decimal:2',
        'limo_9' => 'decimal:2',
        'limo_11' => 'decimal:2',
        'driver_self_support' => 'decimal:2',
    ];

    /** @return MorphMany<ReferencePricingRevision, $this> */
    public function referencePricingRevisions(): MorphMany
    {
        return $this->morphMany(ReferencePricingRevision::class, 'revisionable');
    }
}
