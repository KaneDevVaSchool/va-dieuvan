<?php

namespace App\Services\Admin;

use App\Models\Role;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Thêm tay người dùng không có trong CMS.
 *
 * Người dùng đăng nhập bằng Google với đúng email đã khai: GoogleAuthController tìm user theo email
 * trước khi kiểm tra domain, nên email ngoài domain trường (vd. gmail) cũng vào được sau khi được thêm ở đây.
 */
final class UserProvisionService
{
    public function __construct(
        private readonly UserRoleService $roles,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, employee_code?: string|null, phone?: string|null, role_id?: int|null}  $data
     */
    public function create(array $data, ?int $actorId): User
    {
        return DB::transaction(function () use ($data, $actorId) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'employee_code' => $data['employee_code'] ?? null,
                'phone' => $data['phone'] ?? null,
                // Không có đăng nhập bằng mật khẩu trên UI — mật khẩu ngẫu nhiên chỉ để thỏa cột bắt buộc.
                'password' => Hash::make(Str::random(40)),
                'is_active' => true,
                'source' => User::SOURCE_MANUAL,
            ]);

            $this->audit->log(
                actorId: $actorId,
                event: 'user.created_manually',
                auditable: $user,
                before: null,
                after: $user->only(['id', 'name', 'email', 'employee_code', 'phone']),
                metadata: ['source' => 'admin_user_roles'],
            );

            if (! empty($data['role_id'])) {
                /** @var Role $role */
                $role = Role::query()->findOrFail($data['role_id']);
                // Kiểm tra chống cấp quyền superadmin trái phép nằm trong syncPrimaryRole → lỗi thì rollback cả user.
                $this->roles->syncPrimaryRole($user, $role, $actorId);
            }

            return $user->fresh(['roles:id,name,display_name,guard_name']);
        });
    }
}
