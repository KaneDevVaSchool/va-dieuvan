<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'email',
        'national_id',
        'license_class',
        'license_expires_at',
        'employment_status',
        'availability_status',
        'odometer_km',
    ];

    protected $casts = [
        'license_expires_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function complianceDocuments(): HasMany
    {
        return $this->hasMany(DriverComplianceDocument::class);
    }
}
