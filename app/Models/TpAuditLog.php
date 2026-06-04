<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'entity_type', 'entity_id', 'program_id', 'action',
        'actor_id', 'actor_name', 'before_state', 'after_state', 'metadata',
    ];

    protected $casts = [
        'before_state' => 'array',
        'after_state' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'program_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
