<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyGenerationRun extends Model
{
    protected $fillable = [
        'p2p_policy_term_id',
        'status',
        'total_days',
        'processed_days',
        'total_slots',
        'created_slots',
        'skipped_slots',
        'error_message',
        'started_at',
        'finished_at',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function p2pPolicyTerm(): BelongsTo
    {
        return $this->belongsTo(P2pPolicyTerm::class);
    }
}
