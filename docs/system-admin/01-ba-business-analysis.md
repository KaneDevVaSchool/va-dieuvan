# 01 — Business Analysis (BA)

> Đủ chi tiết để BA viết BRD và QA viết Test Case. Acceptance Criteria viết dạng **Given / When / Then**.

## Mục lục

- [A. Business Flows](#a-business-flows)
- [B. Use Cases](#b-use-cases)
- [C. User Stories + Acceptance Criteria](#c-user-stories--acceptance-criteria)
  - [Epic 1 — Roles](#epic-1--vai-trò-roles)
  - [Epic 2 — Permissions](#epic-2--quyền-thao-tác-permissions)
  - [Epic 3 — Role Assignment](#epic-3--gán-vai-trò-role-assignment)
  - [Epic 4 — Menu Management](#epic-4--quản-lý-menu)
  - [Epic 5 — Audit Logs](#epic-5--nhật-ký-hoạt-động)
  - [Epic 6 — Dashboard](#epic-6--dashboard-hệ-thống)
- [D. Business Rules tổng hợp](#d-business-rules-tổng-hợp)
- [E. Ma trận quyền truy cập màn hình](#e-ma-trận-quyền-truy-cập-màn-hình)

---

## A. Business Flows

### BF-01 — Tạo & cấp quyền cho một vai trò mới (end-to-end)

```
[Admin] mở Vai trò
   │
   ├─► Tạo vai trò mới (mã, tên, mô tả, màu, thứ tự)        ──► Role (status = active)
   │        │
   │        └─(tùy chọn) Clone từ vai trò có sẵn ──► copy toàn bộ permission
   │
   ├─► Mở Quyền thao tác (Matrix) ──► tick quyền cho vai trò
   │        (tick theo module / theo nhóm CRUD / theo cột quyền)
   │        │
   │        └─► Lưu ──► ghi Audit Log (before/after permission set)
   │
   └─► Mở Gán vai trò ──► chọn user / phòng ban ──► gán (hiệu lực từ–đến)
            │
            └─► User có Effective Permissions mới ở lần request kế tiếp
```

### BF-02 — Vai trò tạm thời (cấp quyền có thời hạn)

```
[Admin] Gán vai trò → chọn user → bật "Tạm thời"
   → đặt effective_from = hôm nay, effective_to = +7 ngày
   → Lưu (Audit Log: assign, metadata.temporary=true)
        │
   [Hệ thống] mỗi lần kiểm tra quyền: chỉ tính role có (from ≤ now ≤ to)
        │
   [Cron hằng ngày] đánh dấu assignment hết hạn → status=expired → Audit Log auto-revoke
```

### BF-03 — Khóa & xóa mềm vai trò

```
[Admin] chọn vai trò → "Khóa"
   → status = locked (user giữ role nhưng quyền bị vô hiệu khi đăng nhập)
   → Audit Log
        │
[Admin] "Xóa" vai trò
   → kiểm tra: còn user đang gán? ──Yes──► chặn / yêu cầu thu hồi trước
                                  ──No───► soft delete (deleted_at) → Audit Log
        │
[Admin] "Khôi phục" từ thùng rác → status=active
```

### BF-04 — Quản lý menu động

```
[Admin] Quản lý menu
   ├─ Tạo nhóm menu (group) ──► Menu Item (type=group)
   ├─ Tạo mục menu (item: route/url, icon, badge, target) dưới nhóm
   ├─ Kéo–thả sắp xếp + đổi cha-con ──► cập nhật order/parent_id (batch)
   ├─ Ẩn/Hiện (is_visible)
   └─ Gán permission cho mục ──► chỉ user đủ quyền mới thấy
        │
   [Hệ thống] /api/menu/me trả menu đã lọc theo quyền + feature toggle cho user hiện tại
```

### BF-05 — Điều tra qua Audit Log

```
[Auditor] Nhật ký hoạt động
   ├─ Filter (thời gian, người thao tác, module, hành động, IP)
   ├─ Mở 1 log ──► Drawer chi tiết
   │        ├─ Thông tin: actor, IP, device, browser, module, action, time
   │        └─ Diff Viewer: Before | After (highlight thay đổi)
   └─ Export kết quả filter (Excel/CSV)
```

---

## B. Use Cases

### Bảng tổng hợp Use Case

| ID | Use Case | Actor chính | Module |
|----|----------|-------------|--------|
| UC-R1 | Xem danh sách vai trò (search/filter/sort) | Admin | Roles |
| UC-R2 | Tạo vai trò | Admin | Roles |
| UC-R3 | Sửa vai trò | Admin | Roles |
| UC-R4 | Clone vai trò (kèm/không kèm quyền) | Admin | Roles |
| UC-R5 | Sao chép quyền giữa 2 vai trò | Admin | Roles |
| UC-R6 | Kích hoạt / Khóa vai trò | Admin | Roles |
| UC-R7 | Xóa mềm / Khôi phục vai trò | Admin | Roles |
| UC-R8 | Xem lịch sử thay đổi vai trò | Admin/Auditor | Roles |
| UC-P1 | Xem ma trận Role × Permission | Admin | Permissions |
| UC-P2 | Tick/bỏ tick quyền (đơn lẻ) | Admin | Permissions |
| UC-P3 | Tick hàng loạt (theo module / nhóm / cột quyền) | Admin | Permissions |
| UC-P4 | Lưu thay đổi quyền (ghi audit before/after) | Admin | Permissions |
| UC-A1 | Xem danh sách user + panel quyền | Admin | Assignment |
| UC-A2 | Gán vai trò cho 1 user (hiệu lực từ–đến) | Admin | Assignment |
| UC-A3 | Gán hàng loạt (nhiều user / theo phòng ban) | Admin | Assignment |
| UC-A4 | Thu hồi hàng loạt | Admin | Assignment |
| UC-A5 | Xem Effective Permissions của user | Admin | Assignment |
| UC-M1 | Xem cây menu | Admin | Menu |
| UC-M2 | Tạo menu / nhóm menu | Admin | Menu |
| UC-M3 | Kéo–thả sắp xếp / đổi cấp | Admin | Menu |
| UC-M4 | Ẩn/Hiện + phân quyền menu | Admin | Menu |
| UC-M5 | Sửa thuộc tính menu (icon/badge/route/target) | Admin | Menu |
| UC-L1 | Xem & lọc audit log | Auditor/Admin | Audit |
| UC-L2 | Xem chi tiết + Diff Viewer | Auditor/Admin | Audit |
| UC-L3 | Export audit log | Auditor/Admin | Audit |
| UC-D1 | Xem dashboard hệ thống | Admin | Dashboard |

### UC chi tiết (mẫu — viết đủ cho 3 UC cốt lõi, các UC còn lại dùng cùng template)

#### UC-R2 — Tạo vai trò

| Trường | Nội dung |
|---|---|
| **Actor** | Admin / Super Admin |
| **Tiền điều kiện** | Có quyền `role.create`; đã đăng nhập |
| **Trigger** | Bấm "Tạo vai trò" |
| **Luồng chính** | 1. Hệ thống mở Drawer "Tạo vai trò". 2. Admin nhập: mã (code), tên, mô tả, màu, thứ tự. 3. (Tùy chọn) chọn "Clone từ vai trò" → copy quyền. 4. Bấm Lưu. 5. Hệ thống validate (mã unique, định dạng). 6. Tạo role (status=active), ghi Audit Log. 7. Toast thành công, Drawer đóng, list refresh. |
| **Luồng phụ / ngoại lệ** | 5a. Mã trùng → báo lỗi inline, không đóng Drawer. 4a. Để trống tên → nút Lưu disabled. |
| **Hậu điều kiện** | Role mới xuất hiện trong list; Audit Log `role.create` ghi nhận. |

#### UC-A2 — Gán vai trò cho user (hiệu lực từ–đến)

| Trường | Nội dung |
|---|---|
| **Actor** | Admin |
| **Tiền điều kiện** | Có quyền `assignment.assign` |
| **Luồng chính** | 1. Chọn user trong list. 2. Panel phải hiển thị role hiện tại + quyền thực tế. 3. Bấm "Gán vai trò". 4. Chọn role; (tùy chọn) bật "Tạm thời" + chọn `from`/`to`. 5. Lưu → validate ngày → tạo assignment → Audit Log → cập nhật panel. |
| **Ngoại lệ** | 4a. `to` < `from` → lỗi. 4b. User đã có role đó còn hiệu lực → cảnh báo trùng, cho phép gia hạn thay vì tạo mới. |
| **Hậu điều kiện** | Assignment active; Effective Permissions cập nhật ở request kế tiếp. |

#### UC-L2 — Xem chi tiết Audit + Diff Viewer

| Trường | Nội dung |
|---|---|
| **Actor** | Auditor / Admin |
| **Tiền điều kiện** | Quyền `audit.view` |
| **Luồng chính** | 1. Bấm 1 dòng log. 2. Drawer mở: actor, time, IP, device, browser, module, action. 3. Tab "Thay đổi": Diff Viewer hiển thị Before (trái) / After (phải), highlight field đổi. 4. Có thể copy JSON. |
| **Ngoại lệ** | 3a. Log không có before/after (vd login) → ẩn tab Diff, chỉ hiện metadata. |

---

## C. User Stories + Acceptance Criteria

> Format: **US-x.y** — As a *<role>*, I want *<goal>*, so that *<value>*. + AC dạng Given/When/Then.

### Epic 1 — Vai trò (Roles)

**US-1.1** — *As an Admin, I want to see a searchable/filterable/sortable list of roles, so that I can quickly find a role to manage.*

- **AC-1.1.1** Given có ≥1 vai trò, When mở màn Vai trò, Then thấy bảng gồm: mã, tên, màu (chip), số quyền, số user, trạng thái, cập nhật cuối.
- **AC-1.1.2** Given danh sách, When gõ từ khóa vào ô tìm kiếm, Then list lọc theo mã/tên/mô tả (debounce ≤ 400ms).
- **AC-1.1.3** Given danh sách, When chọn filter trạng thái = "Khóa", Then chỉ hiển thị role locked.
- **AC-1.1.4** Given danh sách, When bấm tiêu đề cột "Cập nhật cuối", Then sort tăng/giảm dần.
- **AC-1.1.5** Given >25 vai trò, When cuộn/đổi trang, Then dùng server pagination, không tải toàn bộ một lần.

**US-1.2** — *As an Admin, I want to create a role with code/name/description/color/order, so that roles are visually distinct and ordered.*

- **AC-1.2.1** Given Drawer tạo, When để trống Tên, Then nút Lưu disabled.
- **AC-1.2.2** Given nhập mã đã tồn tại, When Lưu, Then báo lỗi "Mã vai trò đã tồn tại", Drawer không đóng.
- **AC-1.2.3** Given hợp lệ, When Lưu, Then role tạo với status=active, xuất hiện trong list, Audit Log `role.create` ghi nhận actor + after.
- **AC-1.2.4** Mã vai trò chỉ gồm `[a-z0-9_]`, 2–50 ký tự; tự gợi ý từ Tên.

**US-1.3** — *As an Admin, I want to clone a role, so that I can reuse an existing permission set.*

- **AC-1.3.1** Given chọn "Clone" trên role A, When xác nhận, Then tạo role mới `A_copy` (mã unique) **copy toàn bộ permission** của A.
- **AC-1.3.2** Given clone, When chọn "Không copy quyền", Then role mới rỗng quyền.
- **AC-1.3.3** Clone ghi Audit Log `role.clone` (metadata: source_role_id).

**US-1.4** — *As an Admin, I want to copy permissions from one role to another, so that I align permissions without recreating.*

- **AC-1.4.1** Given chọn nguồn + đích, When "Sao chép quyền", Then hiển thị preview diff (thêm/bớt) trước khi áp dụng.
- **AC-1.4.2** Given xác nhận, Then quyền đích = quyền nguồn (ghi đè), Audit Log before/after.
- **AC-1.4.3** Không cho sao chép vào chính nó.

**US-1.5** — *As an Admin, I want to lock/activate a role, so that I can temporarily disable its effect.*

- **AC-1.5.1** Given role active, When "Khóa", Then status=locked; user mang role này **không nhận quyền** từ role khi đăng nhập/refresh token.
- **AC-1.5.2** Given role locked, When "Kích hoạt", Then status=active.
- **AC-1.5.3** Role hệ thống (`is_system=true`, vd superadmin) **không cho khóa**.

**US-1.6** — *As an Admin, I want to soft-delete and restore roles, so that deletions are reversible.*

- **AC-1.6.1** Given role còn user đang gán hiệu lực, When "Xóa", Then chặn + thông báo "Còn N user đang gán".
- **AC-1.6.2** Given role không còn user, When "Xóa", Then set deleted_at, ẩn khỏi list mặc định, vào tab "Thùng rác".
- **AC-1.6.3** Given role trong thùng rác, When "Khôi phục", Then trở lại active.
- **AC-1.6.4** Role hệ thống không cho xóa.

**US-1.7** — *As an Admin/Auditor, I want to view a role's change history, so that I can audit who changed what.*

- **AC-1.7.1** Given mở 1 role, When chọn tab "Lịch sử", Then thấy timeline các thay đổi (tạo/sửa/khóa/cấp quyền) với actor + thời gian.
- **AC-1.7.2** Mỗi mục lịch sử mở được Diff Before/After.

### Epic 2 — Quyền thao tác (Permissions)

**US-2.1** — *As an Admin, I want a Role × Permission matrix grouped by module, so that I can manage permissions visually.*

- **AC-2.1.1** Given mở Ma trận, Then hàng = permission gom theo **module** (collapsible), cột = role (hoặc ngược lại theo chế độ xem); ô = checkbox.
- **AC-2.1.2** Permission nhóm theo **loại**: CRUD (View/Create/Update/Delete) và Workflow (Submit/Approve/Reject/Cancel) + đặc thù (Export/Import/Duyệt…).
- **AC-2.1.3** Given module lớn, When mở matrix, Then nội dung module load lazy (không render toàn bộ permission cùng lúc).

**US-2.2** — *As an Admin, I want bulk-tick by module / by permission-group / by permission-column, so that I configure fast.*

- **AC-2.2.1** Given 1 module, When tick checkbox header module cho role X, Then **toàn bộ** permission trong module được tick cho X.
- **AC-2.2.2** Given nhóm "CRUD", When tick header nhóm, Then mọi quyền CRUD trong module được tick.
- **AC-2.2.3** Given 1 cột role, When tick header cột, Then mọi permission đang hiển thị (theo filter) được tick cho role đó.
- **AC-2.2.4** Trạng thái header có 3 mức: trống / một phần (indeterminate) / đầy đủ.

**US-2.3** — *As an Admin, I want changes staged and saved atomically with diff, so that I don't lose work and can review.*

- **AC-2.3.1** Given thay đổi nhiều ô, Then hiện sticky bar "N thay đổi chưa lưu" + nút Lưu/Hủy.
- **AC-2.3.2** Given bấm Lưu, Then gửi 1 request batch; nếu lỗi, không áp dụng phần nào (atomic) + báo lỗi.
- **AC-2.3.3** Given Lưu thành công, Then Audit Log `permission.sync` với before/after theo từng role.
- **AC-2.3.4** Given rời trang khi còn thay đổi, Then cảnh báo "Bạn có thay đổi chưa lưu".

**US-2.4** — *As a Super Admin, I want some permissions marked "sensitive", so that only super admins can grant them.*

- **AC-2.4.1** Given permission `is_sensitive=true` (vd quản trị quyền), When Admin (không phải superadmin) thao tác, Then ô disabled + tooltip.

### Epic 3 — Gán vai trò (Role Assignment)

**US-3.1** — *As an Admin, I want a user list with a right-side panel showing current roles & effective permissions.*

- **AC-3.1.1** Given chọn 1 user, Then panel phải hiện: vai trò hiện tại (chip màu), vai trò kế thừa (nếu role có parent), **quyền thực tế** (gộp, có nguồn gốc role).
- **AC-3.1.2** Given user có role hết hạn, Then role đó hiển thị "đã hết hạn", không tính vào quyền thực tế.

**US-3.2** — *As an Admin, I want to assign a role with effective from/to (temporary), so that access can be time-boxed.*

- **AC-3.2.1** Given gán role, When không bật "Tạm thời", Then assignment vô thời hạn (to=null).
- **AC-3.2.2** Given bật "Tạm thời", When `to < from`, Then lỗi validate.
- **AC-3.2.3** Given assignment có `to` đã qua, Then hệ thống không tính quyền role đó; cron đánh dấu expired + Audit Log.
- **AC-3.2.4** Gán ghi Audit Log `assignment.assign` (metadata: from/to, temporary).

**US-3.3** — *As an Admin, I want bulk assign/revoke across many users or by department.*

- **AC-3.3.1** Given chọn nhiều user (checkbox) → "Gán hàng loạt", Then chọn 1+ role + hiệu lực → áp cho tất cả.
- **AC-3.3.2** Given chọn filter phòng ban → "Gán theo phòng ban", Then áp cho mọi user thuộc phòng ban (xác nhận số lượng trước khi chạy).
- **AC-3.3.3** Given "Thu hồi hàng loạt" 1 role, Then gỡ role khỏi các user đã chọn, Audit Log từng user.
- **AC-3.3.4** Bulk action chạy nền nếu >100 user; hiển thị tiến trình + kết quả (thành công/thất bại).

**US-3.4** — *As an Admin, I want to see exactly which permissions a user effectively has and why.*

- **AC-3.4.1** Given panel quyền thực tế, When hover 1 quyền, Then tooltip nêu role nào cấp quyền đó.
- **AC-3.4.2** Có ô tìm kiếm trong danh sách quyền thực tế.

### Epic 4 — Quản lý menu

**US-4.1** — *As an Admin, I want a left tree of menu and a right detail panel, so that I manage structure and properties.*

- **AC-4.1.1** Given mở Quản lý menu, Then sidebar trái = cây menu (nhóm → mục), phải = chi tiết mục đang chọn.

**US-4.2** — *As an Admin, I want to create menu groups and items with icon/badge/route/url/target.*

- **AC-4.2.1** Given tạo "Nhóm menu", Then item type=group, không có route, có thể chứa con.
- **AC-4.2.2** Given tạo "Mục menu", Then nhập: nhãn, icon (chọn từ bộ icon), route **hoặc** url ngoài, target (`_self`/`_blank`), badgeKey (tùy chọn).
- **AC-4.2.3** Mục có URL ngoài + target=_blank phải mở tab mới.

**US-4.3** — *As an Admin, I want drag-and-drop to reorder and re-parent menu items.*

- **AC-4.3.1** Given kéo 1 mục lên/xuống, When thả, Then cập nhật `order` (lưu batch).
- **AC-4.3.2** Given kéo mục vào trong 1 nhóm, Then `parent_id` cập nhật.
- **AC-4.3.3** Không cho kéo nhóm vào trong chính nó / con của nó (chống vòng lặp).
- **AC-4.3.4** Giới hạn độ sâu cây ≤ 3 cấp.

**US-4.4** — *As an Admin, I want to hide/show and permission-bind menu items.*

- **AC-4.4.1** Given toggle "Ẩn", Then `is_visible=false`; mục không xuất hiện ở menu người dùng.
- **AC-4.4.2** Given gán permission cho mục, Then chỉ user có quyền đó mới thấy mục ở `/api/menu/me`.
- **AC-4.4.3** Given mục cha bị ẩn, Then mọi mục con bị ẩn theo.

**US-4.5** — *As any user, my sidebar reflects the dynamic menu filtered by my permissions & feature toggles.*

- **AC-4.5.1** Given đăng nhập, When tải app, Then sidebar render từ `/api/menu/me` (đã lọc quyền + feature toggle + ẩn/hiện).
- **AC-4.5.2** Menu cache phía client; invalidate khi admin lưu thay đổi (version/etag).

### Epic 5 — Nhật ký hoạt động

**US-5.1** — *As an Auditor, I want to view & filter audit logs by time/user/module/action/IP.*

- **AC-5.1.1** Given mở Audit, Then bảng: thời gian, người thao tác, module, hành động, IP, kết quả.
- **AC-5.1.2** Filter: khoảng thời gian, actor, module, action, IP, từ khóa; sticky filter bar.
- **AC-5.1.3** Mặc định hiển thị 30 ngày gần nhất, server pagination.

**US-5.2** — *As an Auditor, I want a detail drawer with Diff Viewer (Before/After).*

- **AC-5.2.1** Given mở 1 log có before/after, Then Diff Viewer 2 cột, highlight field thay đổi (thêm=xanh, xóa=đỏ, đổi=vàng).
- **AC-5.2.2** Hiển thị metadata: IP, device, browser, OS, user-agent gốc (thu gọn).
- **AC-5.2.3** Log không có diff (login/logout/export) → ẩn tab Diff.

**US-5.3** — *As an Auditor, I want to export the filtered log set to Excel/CSV.*

- **AC-5.3.1** Given đang filter, When "Export", Then xuất đúng tập đang lọc (không phải toàn bộ).
- **AC-5.3.2** Export >10k dòng chạy nền + thông báo khi xong.

**US-5.4** — *As the system, every mutation in this module writes an audit entry.*

- **AC-5.4.1** Given bất kỳ create/update/delete/lock/assign/permission-sync, Then có 1 audit entry với actor, event, before/after, ip, device.
- **AC-5.4.2** Theo dõi tối thiểu: login, logout, create, update, delete, import, export, approve, reject.

### Epic 6 — Dashboard hệ thống

**US-6.1** — *As an Admin, I want an overview dashboard of the admin domain.*

- **AC-6.1.1** Widget: Tổng User, Tổng Vai trò, Tổng Quyền, Lượt đăng nhập hôm nay.
- **AC-6.1.2** Widget Top người dùng hoạt động (theo số thao tác trong N ngày).
- **AC-6.1.3** Widget Log bất thường (đăng nhập thất bại nhiều, thao tác nhạy cảm).
- **AC-6.1.4** Widget Cảnh báo bảo mật (vd: nhiều lần đăng nhập sai, role nhạy cảm vừa được cấp).
- **AC-6.1.5** Mỗi widget click được → deep-link sang màn tương ứng (đã filter sẵn).
- **AC-6.1.6** Số liệu cache ngắn (vd 60s) để không nặng DB.

---

## D. Business Rules tổng hợp

| ID | Business Rule |
|----|---------------|
| BR-01 | Mã vai trò: `[a-z0-9_]`, 2–50 ký tự, **unique toàn hệ thống**, không đổi sau khi tạo (chỉ sửa với role thường, role hệ thống cố định). |
| BR-02 | Vai trò hệ thống (`is_system=true`): không xóa, không khóa, không đổi mã; chỉ Super Admin chỉnh quyền. |
| BR-03 | Không xóa được vai trò còn user gán đang hiệu lực. |
| BR-04 | Xóa vai trò = **xóa mềm** (deleted_at); có thể khôi phục. |
| BR-05 | Effective Permissions của user = hợp quyền của các role **đang hiệu lực** (active + trong khoảng from–to) + quyền gán trực tiếp − quyền của role đang locked. |
| BR-06 | Assignment "tạm thời": `effective_to` bắt buộc; hết hạn → tự expired (cron) + ghi audit. |
| BR-07 | `effective_to ≥ effective_from`. |
| BR-08 | Permission `is_sensitive=true` chỉ Super Admin được cấp/thu. |
| BR-09 | Menu: độ sâu ≤ 3 cấp; không tạo vòng lặp cha-con. |
| BR-10 | Mục menu cha ẩn → toàn bộ con ẩn theo (effective visibility). |
| BR-11 | Mục menu chỉ hiển thị cho user nếu: visible = true AND (không gắn permission OR user có permission) AND feature toggle bật. |
| BR-12 | Mọi mutation trong module ghi Audit Log (BR bắt buộc, không bỏ qua). |
| BR-13 | Audit Log **append-only**: không sửa, không xóa qua UI (chỉ purge theo retention policy ở backend/cron). |
| BR-14 | Bulk action >N (vd 100) chạy bất đồng bộ (queue) + báo tiến trình. |
| BR-15 | Export tôn trọng filter hiện tại; >10k dòng chạy nền. |

## E. Ma trận quyền truy cập màn hình

| Màn | Permission xem | Permission thao tác |
|-----|----------------|---------------------|
| Dashboard | `system.dashboard.view` | — |
| Vai trò | `role.view` | `role.create`, `role.update`, `role.delete`, `role.clone` |
| Quyền (Matrix) | `permission.view` | `permission.sync` (+ `permission.grant_sensitive` cho quyền nhạy cảm) |
| Gán vai trò | `assignment.view` | `assignment.assign`, `assignment.revoke` |
| Quản lý menu | `menu.view` | `menu.manage` |
| Nhật ký | `audit.view` | `audit.export` |

> Tên permission key chi tiết & mapping từ key cũ: xem [03-technical-architecture.md](./03-technical-architecture.md#5-permission-architecture).
