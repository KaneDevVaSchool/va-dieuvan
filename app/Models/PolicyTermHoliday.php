<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyTermHoliday extends Model
{
    protected $fillable = [
        'p2p_policy_term_id',
        'holiday_date',
        'label',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];

    public function p2pPolicyTerm(): BelongsTo
    {
        return $this->belongsTo(P2pPolicyTerm::class);
    }
}
