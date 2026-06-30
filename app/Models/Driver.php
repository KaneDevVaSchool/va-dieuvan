<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

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

    protected static function booted(): void
    {
        static::deleting(function (Driver $driver): void {
            if ($driver->isForceDeleting()) {
                // Null out the RESTRICT FK in tp_trip_executions before the row is gone.
                // driver_snapshot preserves all driver info, so losing the FK is safe.
                DB::table('tp_trip_executions')
                    ->where('driver_id', $driver->id)
                    ->update(['driver_id' => null]);

                return;
            }

            Trip::where('driver_id', $driver->id)->update(['driver_id' => null]);
            TpProgram::where('default_driver_id', $driver->id)->update(['default_driver_id' => null]);
            TpProgram::where('backup_driver_id', $driver->id)->update(['backup_driver_id' => null]);
        });
    }
}
