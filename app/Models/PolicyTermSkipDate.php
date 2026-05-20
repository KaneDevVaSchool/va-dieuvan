<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyTermSkipDate extends Model
{
    protected $fillable = [
        'p2p_policy_term_id',
        'skip_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'skip_date' => 'date',
    ];

    public function p2pPolicyTerm(): BelongsTo
    {
        return $this->belongsTo(P2pPolicyTerm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
