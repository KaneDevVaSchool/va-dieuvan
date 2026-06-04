# 00 — Overview & Scope

## 1. Bối cảnh

Module **"Hệ Thống"** hiện tại gồm 5 màn rời rạc, xây tăng dần, không có dashboard, không có chuẩn UX chung:

| Màn hiện tại | Route | File | Vấn đề |
|---|---|---|---|
| Vai trò người dùng | `system/roles` | `resources/js/src/views/system/SystemRolesView.vue` | Thiếu màu, thứ tự, trạng thái, xóa mềm, clone, lịch sử |
| Quyền thao tác | `system/permissions` | `SystemPermissionsView.vue` | Ma trận cơ bản; chưa tick theo nhóm/module hàng loạt mạnh |
| Gán vai trò | `system/user-roles` | `SystemUserRolesView.vue` | Không có hiệu lực theo thời gian, không panel "quyền thực tế" |
| Bật tắt tính năng menu | `system/feature-toggles` | `SystemFeatureTogglesView.vue` | Menu **tĩnh** (`config/nav.js`), chỉ bật/tắt; không kéo thả, không tạo menu động |
| Ngưỡng Gấp (Dispatch) | `system/dispatch-settings` | `SystemDispatchSettingsView.vue` | Là cấu hình nghiệp vụ điều vận — **KHÔNG thuộc phạm vi** redesign này |
| Nhật ký hoạt động | `audit-logs` | `views/audit/AuditLogsView.vue` | Thiếu cột IP/thiết bị/trình duyệt tường minh; chưa có Diff Viewer |

> **Lưu ý:** "Ngưỡng Gấp / Dispatch Settings" là cấu hình nghiệp vụ điều vận, được giữ nguyên và **đưa ra khỏi** module Hệ Thống mới (hoặc gắn vào module Điều vận). Không nằm trong rebuild này.

## 2. Mục tiêu

1. Giao diện hiện đại, trực quan, **ít thao tác** (Enterprise SaaS).
2. Quản trị **phân quyền mạnh mẽ** (Role × Permission matrix, gán theo thời gian).
3. Chuẩn UX hiện đại: **Drawer ưu tiên hơn popup**, full-width, sticky filter/action bar, **Dark Mode ready**.
4. **Mở rộng được** không phải đổi kiến trúc (menu động, permission catalog versioned).
5. **Responsive Desktop / Tablet** (mobile chỉ read-only ở mức tối thiểu).
6. Ưu tiên trải nghiệm **quản trị viên**.

## 3. Phạm vi (Scope)

### ✅ In scope

| # | Màn | Mô tả ngắn |
|---|-----|-----------|
| 1 | **Dashboard Hệ Thống** | Widget tổng quan: User, Role, Permission, đăng nhập hôm nay, top user, log bất thường, cảnh báo bảo mật |
| 2 | **Vai Trò (Roles)** | CRUD + clone + sao chép quyền + kích hoạt/khóa + xóa mềm + lịch sử |
| 3 | **Quyền Thao Tác (Permissions)** | Ma trận Role × Permission; CRUD + Workflow; tick hàng loạt theo module/nhóm/quyền |
| 4 | **Gán Vai Trò (Role Assignment)** | Gán/thu hồi hàng loạt, hiệu lực từ–đến ngày, gán theo phòng ban, panel quyền thực tế |
| 5 | **Quản Lý Menu (Menu Management)** | Menu động DB-backed: kéo thả, tạo menu/nhóm, ẩn/hiện, phân quyền, icon/badge/route |
| 6 | **Nhật Ký Hoạt Động (Audit Logs)** | Theo dõi thao tác; IP/thiết bị/trình duyệt; Diff Viewer Before/After |

### ❌ Out of scope (chốt)

- **Data Scope / phân quyền theo phạm vi dữ liệu** (Toàn HT / Pháp nhân / Điểm trường / Phòng ban / Chính mình) → **bỏ tính năng**.
- Bảng tổ chức **Pháp nhân / Điểm trường** → không tạo.
- **Dispatch Settings (Ngưỡng Gấp)** → giữ nguyên, không thuộc rebuild.
- **Quản lý User** (CRUD tài khoản) đầy đủ → chỉ dùng *list + search* phục vụ gán vai trò; CRUD user là module riêng (HR/Nhân sự).
- Mobile phone layout đầy đủ → chỉ Desktop/Tablet.

### 🔸 Điều chỉnh so với spec gốc (do bỏ Data Scope)

| Spec gốc | Điều chỉnh |
|---|---|
| Loại quyền có nhóm **Data Scope** (5 mức) | Bỏ. Chỉ còn **CRUD** + **Workflow**. |
| Gán vai trò "Gán theo điểm trường" | Bỏ. Chỉ còn **gán theo phòng ban**. |
| Permission "Theo pháp nhân/điểm trường/chính mình" | Bỏ. |
| Dashboard "Cảnh báo bảo mật" theo phạm vi | Cảnh báo bảo mật giữ ở mức toàn hệ thống. |

## 4. Information Architecture (IA)

```
Hệ Thống (System Admin)
├── 📊 Dashboard
├── 🛡️  Vai trò               (Roles)
├── 🔑 Quyền thao tác          (Permissions — Role × Permission Matrix)
├── 👥 Gán vai trò             (Role Assignment)
├── 🧭 Quản lý menu            (Menu Management)
└── 📜 Nhật ký hoạt động       (Audit Logs)
```

Sitemap chi tiết & deep-links: xem [02-ux-design.md](./02-ux-design.md#1-sitemap).

## 5. Personas

| Persona | Vai trò | Nhu cầu chính |
|---|---|---|
| **Super Admin** | `superadmin` | Toàn quyền; cấu hình menu, quyền nhạy cảm, xem mọi audit |
| **Quản trị hệ thống** | `admin` | Quản lý vai trò, gán quyền, theo dõi audit; không sửa quyền siêu nhạy cảm |
| **Trưởng đơn vị** | `department_head` | Gán vai trò trong phạm vi phòng ban của mình (nếu được cấp) |
| **Auditor / Bảo mật** | (role mới, tùy chọn) | Chỉ đọc Audit Logs + Dashboard bảo mật |

## 6. Nguyên tắc thiết kế (Design Principles)

1. **Drawer-first** — thao tác chi tiết mở Drawer (slide-over) bên phải, không full-page reload, không modal lồng nhau.
2. **One screen, one job** — mỗi màn một nhiệm vụ rõ ràng; bulk action gom ở Action Bar dính (sticky).
3. **Bulk by default** — mọi danh sách hỗ trợ chọn nhiều + thao tác hàng loạt.
4. **Reversible** — ưu tiên **xóa mềm** + lịch sử; thao tác nguy hiểm cần xác nhận có gõ tên/lý do.
5. **Audit everything** — mọi mutation trong module này ghi Audit Log (actor, before/after, ip, device).
6. **Performance** — danh sách dùng **server pagination** (mặc định) hoặc infinite scroll; ma trận quyền lazy theo module.

## 7. Glossary (Thuật ngữ)

| Thuật ngữ | Định nghĩa |
|---|---|
| **Role (Vai trò)** | Tập hợp quyền có tên, gán cho user. Có mã, màu, thứ tự, trạng thái. |
| **Permission (Quyền)** | Quyền thao tác chi tiết, key dạng `resource.action` (vd `role.create`). |
| **Permission Module** | Nhóm chức năng (Nhân sự, Kho, Mua sắm, Điều vận…) gom các permission. |
| **Action Type** | Loại hành động: **CRUD** (view/create/update/delete) hoặc **Workflow** (submit/approve/reject/cancel) + đặc thù (export/import). |
| **Role Assignment** | Bản ghi gán role cho user, có hiệu lực `from`–`to` (có thể tạm thời). |
| **Effective Permissions** | Tập quyền thực tế của user = hợp của quyền từ các role đang hiệu lực (+ quyền gán trực tiếp). |
| **Menu Item** | Mục menu động lưu DB; có route/url, icon, badge, thứ tự, cha-con, gắn permission. |
| **Audit Log** | Bản ghi thao tác: actor, event, before/after (JSON diff), metadata (ip/device/browser). |
| **System Role** | Vai trò hệ thống (vd `superadmin`) — không cho xóa/đổi mã, chỉ giới hạn chỉnh sửa. |

## 8. Quyết định kiến trúc (Architecture Decisions — tóm tắt)

| ID | Quyết định | Lý do |
|----|-----------|-------|
| AD-01 | Bỏ Data Scope | Stakeholder chốt; giảm ~40% độ phức tạp ERD/API |
| AD-02 | Greenfield drop & recreate schema RBAC | Schema cũ trùng lặp (bảng custom + Spatie); làm sạch |
| AD-03 | Giữ Spatie làm engine phân quyền lõi | Đã cài, ổn định; mở rộng bằng cột/bảng phụ |
| AD-04 | Menu chuyển từ `nav.js` tĩnh → DB-backed động | Yêu cầu kéo thả + tạo menu runtime |
| AD-05 | Audit tách cột IP/device/browser ra ngoài `metadata` | Phục vụ filter/index nhanh + Diff Viewer |
| AD-06 | Role Assignment có cột `effective_from`/`effective_to` | Hỗ trợ vai trò tạm thời |
| AD-07 | Role hỗ trợ `parent_id` (kế thừa quyền, tùy chọn) | Hỗ trợ "vai trò kế thừa" + clone |

Chi tiết kiến trúc & schema: [03-technical-architecture.md](./03-technical-architecture.md).
