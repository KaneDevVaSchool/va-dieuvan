<?php

namespace App\Services\Resources;

use App\Models\Driver;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;

class DriverProfileService
{
    /**
     * Cập nhật hồ sơ tài xế. Hồ sơ chưa nối tài khoản mà nhập email trùng user có sẵn
     * thì tự nối (drivers.user_id) để tài xế đăng nhập thấy chuyến được gán.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Driver $driver, array $data, ?int $actorId): Driver
    {
        return DB::transaction(function () use ($driver, $data, $actorId) {
            $driver->refresh();
            $before = $driver->only(['full_name', 'phone', 'email', 'user_id', 'employment_status', 'availability_status']);
            $linkedUser = null;

            if (array_key_exists('email', $data)) {
                $email = $data['email'];
                unset($data['email']);
                if ($driver->user_id) {
                    $user = $driver->user()->first();
                    if ($user) {
                        $user->email = $email;
                        $user->save();
                    }
                } else {
                    $driver->email = $email;
                    $linkedUser = $this->linkableUser($driver, $email);
                }
            }

            if ($linkedUser) {
                $driver->user_id = $linkedUser->id;
                if (! $linkedUser->hasRole(Roles::DRIVER)) {
                    $linkedUser->assignRole(Roles::DRIVER);
                }
            }

            $driver->fill($data);
            $driver->save();

            app(AuditLogger::class)->log(
                actorId: $actorId,
                event: $linkedUser ? 'driver.user_linked' : 'driver.updated',
                auditable: $driver,
                before: $before,
                after: $driver->only(array_keys($before)),
                metadata: $linkedUser ? ['linked_user' => ['id' => $linkedUser->id, 'email' => $linkedUser->email]] : null,
            );

            return $driver;
        });
    }

    private function linkableUser(Driver $driver, mixed $email): ?User
    {
        $email = is_string($email) ? mb_strtolower(trim($email)) : '';
        if ($email === '') {
            return null;
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return null;
        }

        $other = Driver::query()->where('user_id', $user->id)->whereKeyNot($driver->id)->first(['id', 'full_name']);
        abort_if(
            $other,
            422,
            "Email này thuộc tài khoản đã nối với hồ sơ tài xế #{$other?->id} ({$other?->full_name}). Hãy gộp hoặc gỡ liên kết hồ sơ đó trước.",
        );

        return $user;
    }
}
