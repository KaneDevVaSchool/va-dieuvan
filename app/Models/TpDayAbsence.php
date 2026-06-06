<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpDayAbsence extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'program_day_id', 'student_id', 'shift', 'absence_type', 'category', 'reason_code', 'absence_reason',
        'recorded_by', 'recorded_at', 'source', 'notes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function programDay(): BelongsTo
    {
        return $this->belongsTo(TpProgramDay::class, 'program_day_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpStudent::class, 'student_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
