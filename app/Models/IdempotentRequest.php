<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'scope',
        'key_hash',
        'status_code',
        'response_body',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
