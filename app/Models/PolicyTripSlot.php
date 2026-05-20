<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyTripSlot extends Model
{
    protected $fillable = [
        'p2p_policy_term_id',
        'policy_route_id',
        'run_date',
        'leg',
        'dispatch_request_id',
        'trip_id',
    ];

    protected $casts = [
        'run_date' => 'date',
    ];

    public function p2pPolicyTerm(): BelongsTo
    {
        return $this->belongsTo(P2pPolicyTerm::class);
    }

    public function policyRoute(): BelongsTo
    {
        return $this->belongsTo(PolicyRoute::class);
    }

    public function dispatchRequest(): BelongsTo
    {
        return $this->belongsTo(DispatchRequest::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
