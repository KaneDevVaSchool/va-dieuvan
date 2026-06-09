# Module: Users & Phân quyền

## Mô tả nghiệp vụ

Quản lý người dùng hệ thống và phân quyền theo vai trò (RBAC).

## Roles

| Role | Truy cập | Mô tả |
|------|---------|-------|
| `superadmin` | Toàn bộ | Bypass mọi permission check |
| `admin` | Web + Admin panel | Quản trị hệ thống |
| `dispatcher` | Web dispatch | Điều phối xe |
| `department_head` | Web + Portal (duyệt) | Trưởng đơn vị |
| `internal_user` | Portal | Nhân viên gửi yêu cầu |
| `driver` | /driver only | Tài xế |
| `accountant` | Costs/reports | Kế toán |

## Database

- `users` — Thông tin người dùng
- `departments` — Đơn vị/phòng ban
- `roles`, `permissions` — Spatie RBAC tables
- `model_has_roles` — User → Role mappings
- `role_has_permissions` — Role → Permission mappings
- `role_assignments` — Audit trail cho role changes

## API Endpoints

| Method | Endpoint | Role | Mô tả |
|--------|----------|------|-------|
| GET | `/api/admin/users` | admin | Danh sách users |
| GET | `/api/admin/users/search` | admin | Tìm kiếm |
| POST | `/api/admin/users/bulk-roles` | admin | Gán role hàng loạt |
| GET | `/api/admin/roles` | admin | Danh sách roles |
| POST | `/api/admin/roles` | admin | Tạo role |
| GET | `/api/admin/permissions` | admin | Danh sách permissions |
| GET | `/api/me` | any | Profile hiện tại |
| PUT | `/api/me` | any | Cập nhật profile |

## Permission System

Dùng Spatie Laravel Permission v6:

```php
// Kiểm tra permission
$user->hasPermission('trips.assign')

// Kiểm tra role
$user->hasRole('dispatcher')
$user->hasAnyRole(Roles::DISPATCH_WEB_ROLES)

// SuperAdmin bypass
$user->isSuperAdmin() // true nếu email match hoặc có role superadmin
```

## Business Rules

1. SuperAdmin có thể được xác định qua email (SuperAdminAccess class) hoặc role
2. User đăng nhập qua Google OAuth (`google_id`)
3. `is_active = false` không được phép đăng nhập
4. `primary_role_name` là role hiển thị chính (không ảnh hưởng permissions)
5. Một user có thể có nhiều roles
