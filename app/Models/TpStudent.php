<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TpStudent extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_GRADUATED = 'graduated';

    protected $fillable = [
        'code', 'full_name', 'grade', 'class_name', 'campus_id',
        'parent_name', 'parent_phone', 'address', 'status',
        'metadata', 'source', 'external_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(TpEnrollment::class, 'student_id');
    }

    public function tripLogs(): HasMany
    {
        return $this->hasMany(TpTripStudentLog::class, 'student_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByClass(Builder $query, string $className): Builder
    {
        return $query->where('class_name', $className);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }
        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('full_name', 'like', $like)
                ->orWhere('code', 'like', $like);
        });
    }
}
