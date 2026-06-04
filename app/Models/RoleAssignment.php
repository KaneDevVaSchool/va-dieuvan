<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleAssignment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'user_id',
        'role_id',
        'effective_from',
        'effective_to',
        'status',
        'is_temporary',
        'assigned_by',
        'revoked_by',
        'revoked_at',
        'note',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_temporary' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isEffectiveOn(string $date): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        if ($this->effective_from && $this->effective_from->format('Y-m-d') > $date) {
            return false;
        }
        if ($this->effective_to && $this->effective_to->format('Y-m-d') < $date) {
            return false;
        }

        return true;
    }
}
