<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'phone',
        'employee_code',
        'department_id',
        'is_active',
        'primary_role_name',
        'primary_role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isSuperAdmin(): bool
    {
        $email = config('permission.superadmin_email');
        if ($email && $this->email === $email) {
            return true;
        }

        $roleName = config('permission.superadmin_role', 'superadmin');

        return $this->hasRole($roleName);
    }

    /** Web SPA điều vận: superadmin (email hoặc role), admin, dispatcher, trưởng đơn vị, user nội bộ. */
    public function canAccessDispatchWebApp(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole(['admin', 'dispatcher', 'department_head', 'internal_user']);
    }

    /** Khu vực web tài xế (`/driver`). */
    public function canAccessDriverWebApp(): bool
    {
        return $this->hasRole('driver');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Kiểm tra quyền theo tên (tương thích code cũ). SuperAdmin luôn true.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasPermissionTo($permissionName);
    }
}
