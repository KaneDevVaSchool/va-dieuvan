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

        return $query->where(function (Builder $q) use ($like, $term) {
            $q->where('full_name', 'like', $like)
                ->orWhere('code', 'like', $like)
                ->orWhere('parent_name', 'like', $like)
                ->orWhere('parent_phone', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('class_name', 'like', $like)
                ->orWhere('grade', 'like', $like);

            foreach (['gender', 'father_name', 'father_phone', 'mother_name', 'mother_phone', 'pickup_point', 'note'] as $key) {
                $q->orWhere('metadata->'.$key, 'like', $like);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($term))) {
                $q->orWhere('metadata->date_of_birth', trim($term));
            }
        });
    }
}
