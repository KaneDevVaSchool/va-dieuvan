<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyTripAudit extends Model
{
    public $timestamps = false;

    protected $table = 'policy_trip_audit';

    protected $fillable = [
        'policy_trip_id',
        'changed_by',
        'changed_at',
        'action',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function policyTrip(): BelongsTo
    {
        return $this->belongsTo(PolicyTrip::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
