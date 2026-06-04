<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpEnrollment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'program_id', 'student_id', 'enrolled_at', 'enrolled_by',
        'unenrolled_at', 'unenrolled_by', 'unenroll_reason', 'notes',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'unenrolled_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(TpProgram::class, 'program_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpStudent::class, 'student_id');
    }

    public function enrolledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('unenrolled_at');
    }

    public function isActive(): bool
    {
        return $this->unenrolled_at === null;
    }
}
