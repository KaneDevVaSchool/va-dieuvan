<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'full_name',
        'date_of_birth',
        'grade',
        'guardian_name',
        'guardian_phone',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'route_students')
            ->withPivot(['starts_on', 'ends_on'])
            ->withTimestamps();
    }
}

