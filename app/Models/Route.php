<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Route extends Model
{
    use HasFactory;

    public const TYPE_D2D = 'door_to_door';

    /** Tuyến chỉ dùng cho đưa đón học sinh chính sách (P2P), tách khỏi D2D. */
    public const TYPE_POLICY = 'policy';

    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(RouteVersion::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'route_students')
            ->withPivot(['starts_on', 'ends_on'])
            ->withTimestamps();
    }
}

