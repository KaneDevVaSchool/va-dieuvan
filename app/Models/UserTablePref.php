<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTablePref extends Model
{
    protected $fillable = [
        'user_id',
        'table_key',
        'columns',
        'saved_filters',
        'default_filter_id',
    ];

    protected $casts = [
        'columns' => 'array',
        'saved_filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
