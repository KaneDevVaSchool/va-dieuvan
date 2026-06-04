<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCalendar extends Model
{
    protected $fillable = [
        'school_year',
        'semester',
        'date',
        'day_type',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'semester' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
