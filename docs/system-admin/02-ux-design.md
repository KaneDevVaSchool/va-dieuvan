# 02 — UX Design

> Đủ chi tiết để UI/UX dựng Figma. Wireframe dạng ASCII (low-fi) + đặc tả tương tác, responsive, design tokens, component.

## Mục lục

1. [Sitemap](#1-sitemap)
2. [User Flows](#2-user-flows)
3. [Layout chung (App Shell)](#3-layout-chung-app-shell)
4. [Design System & Tokens](#4-design-system--tokens)
5. [Data Table chuẩn (bắt buộc)](#5-data-table-chuẩn-bắt-buộc)
6. [Wireframes theo màn](#6-wireframes-theo-màn)
7. [Responsive Layout](#7-responsive-layout)
8. [Component Inventory](#8-component-inventory)
9. [Interaction & Motion](#9-interaction--motion)
10. [Empty / Loading / Error states](#10-empty--loading--error-states)

---

## 1. Sitemap

```
/system                         (Hệ Thống — layout chung, sidebar con)
├── /system/dashboard           Dashboard hệ thống
├── /system/roles               Vai trò
│     └── (Drawer) ?role=:id    Chi tiết / Sửa / Lịch sử
├── /system/permissions         Quyền thao tác (Matrix)
├── /system/assignments         Gán vai trò
│     └── (Panel) ?user=:id     Quyền thực tế của user
├── /system/menu                Quản lý menu
└── /system/audit               Nhật ký hoạt động
      └── (Drawer) ?log=:id     Chi tiết + Diff Viewer
```

**Điều hướng:** module Hệ Thống có **secondary sidebar** (cấp 2) riêng bên trái khu nội dung, tách khỏi sidebar chính của app. Breadcrumb: `Hệ Thống / <Màn> / <Đối tượng>`.

> Route đề xuất gộp về tiền tố `/system/*` (hiện `audit-logs` đứng riêng — sẽ chuyển thành `/system/audit`).

---

## 2. User Flows

### UF-A — Tạo vai trò + cấp quyền + gán (happy path)

```
Roles ──"Tạo vai trò"──► Drawer (form) ──Lưu──► Toast ✓ ──► Roles (row mới, highlight)
   │
   └─"Cấp quyền"──► Permissions (matrix, prefilter role) ──tick──► Sticky bar "Lưu" ──► Toast ✓
        │
        └─► Assignments ──chọn user/phòng ban──► "Gán" (Drawer) ──Lưu──► Panel cập nhật
```

### UF-B — Gán vai trò tạm thời

```
Assignments ─chọn user─► Panel phải ─"Gán vai trò"─► Drawer
   └─ chọn Role ─ bật [Tạm thời] ─ from/to ─ Lưu ─► chip role có badge "đến dd/mm"
```

### UF-C — Kéo thả menu

```
Menu ─ kéo item ─ drop (line indicator) ─► optimistic update ─► PATCH /menu/reorder
   └─ lỗi ─► rollback vị trí + toast lỗi
```

### UF-D — Điều tra audit

```
Audit ─ set filter (sticky) ─► list ─ click row ─► Drawer (tabs: Tổng quan | Thay đổi)
   └─ "Export" ─► tải file theo filter
```

---

## 3. Layout chung (App Shell)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ TOPBAR: Logo  |  Hệ Thống ▸ Vai trò            🔎  🌗(dark)  🔔  ⏣ Admin ▾ │
├───────────────┬──────────────────────────────────────────────────────────┤
│ SECONDARY     │  PAGE HEADER (sticky)                                      │
│ SIDEBAR       │  ┌────────────────────────────────────────────────────┐   │
│ (system nav)  │  │ H1 Vai trò        [+ Tạo vai trò]  [⋯ Khác]        │   │
│               │  │ Subtitle / breadcrumb                               │   │
│ 📊 Dashboard  │  └────────────────────────────────────────────────────┘   │
│ 🛡 Vai trò ◄  │  FILTER BAR (sticky)                                       │
│ 🔑 Quyền      │  [🔎 Tìm...] [Trạng thái ▾][Sắp xếp ▾] [Saved ▾][⚙ Cột]   │
│ 👥 Gán vai trò│  ┌────────────────────────────────────────────────────┐   │
│ 🧭 Menu       │  │                  CONTENT (full-width)              │   │
│ 📜 Nhật ký    │  │                  Data Table / Matrix / ...         │   │
│               │  │                                                    │   │
│ ─────────     │  └────────────────────────────────────────────────────┘   │
│ ◀ Thu gọn     │  ACTION BAR (sticky, hiện khi có selection)               │
│               │  [✔ 3 đã chọn]  [Gán][Khóa][Xóa]            [Bỏ chọn]     │
└───────────────┴──────────────────────────────────────────────────────────┘
                 ▶ DRAWER (slide-over, phủ phải) khi xem/sửa chi tiết
```

**Nguyên tắc bố cục**
- **Full-width** content, không giới hạn max-width hẹp.
- **Sticky**: Page header + Filter bar (trên), Action bar (dưới khi có chọn).
- **Drawer-first**: chi tiết/sửa mở Drawer phải (rộng 480–640px, có thể giãn). Modal **chỉ** dùng cho xác nhận nguy hiểm (xóa, thu hồi).
- **Dark mode**: toàn bộ token theo `class="dark"` (đã có `ThemeSwitcher.vue`).

---

## 4. Design System & Tokens

Kế thừa Tailwind config hiện có. Token đề xuất (semantic):

| Token | Light | Dark | Dùng cho |
|---|---|---|---|
| `surface` | `white` | `slate-900` | nền card/drawer |
| `surface-muted` | `slate-50` | `slate-800` | nền filter bar, header |
| `border` | `slate-200` | `slate-700` | viền |
| `text` | `slate-900` | `slate-100` | chữ chính |
| `text-muted` | `slate-500` | `slate-400` | chữ phụ |
| `primary` | `indigo-600` | `indigo-400` | nút chính, link |
| `success/warning/danger` | green/amber/red-600 | -400 | trạng thái |

**Role color**: bộ màu định sẵn (12 màu, contrast đạt AA) cho user chọn — không cho nhập hex tự do (giữ nhất quán). Chip role = nền nhạt + chữ đậm cùng tông.

**Spacing/Radius/Elevation**: radius `rounded-xl` cho card/drawer, `rounded-lg` cho input; shadow nhẹ (`shadow-sm`), Drawer dùng `shadow-xl`.

**Typography**: H1 20–24px/semibold, H2 16px/semibold, body 14px, caption 12px. Số liệu dashboard 28–32px/bold.

**Iconography**: Heroicons (đã cài `@heroicons/vue`). Menu icon chọn từ `navIconMap.js` mở rộng.

**Accessibility**: contrast ≥ AA; focus ring rõ; mọi action có aria-label; bảng có scope header; Drawer trap focus + ESC để đóng.

---

## 5. Data Table chuẩn (bắt buộc)

Component dùng chung cho mọi danh sách trong module. **Bắt buộc** các tính năng:

```
┌─ FILTER BAR ───────────────────────────────────────────────────────────┐
│ [🔎 Tìm...]  [+ Bộ lọc ▾ (Filter Builder)]  [💾 Bộ lọc đã lưu ▾]        │
│                                          [⚙ Ẩn/hiện cột]  [⬇ Export ▾]  │
├─ TABLE HEAD (sticky) ──────────────────────────────────────────────────┤
│ [☑] │ Cột A ↕ │ Cột B ↕ │ Cột C ↕ │ ... │ [⋮ actions]                   │
├────────────────────────────────────────────────────────────────────────┤
│ [☑] │  ...    │  ...    │  ...    │     │ [⋯ row menu]                  │
│ ... (server pagination HOẶC infinite scroll)                            │
├─ FOOTER ───────────────────────────────────────────────────────────────┤
│ Tổng 245 · Trang 1/10 · [10▾ /trang]            [◀ 1 2 3 ... 10 ▶]      │
└────────────────────────────────────────────────────────────────────────┘
```

| Tính năng | Đặc tả |
|---|---|
| **Ẩn/hiện cột** | Menu checkbox; kéo sắp xếp thứ tự cột |
| **Lưu layout cá nhân** | Lưu cấu hình cột + filter mặc định theo user (localStorage + `/api/me/table-prefs`) |
| **Filter Builder** | Điều kiện dạng `field — operator — value`, AND/OR; nhiều dòng |
| **Saved Filter** | Lưu/đặt tên/đặt mặc định bộ lọc; chia sẻ tùy chọn |
| **Export** | Excel (.xlsx) + CSV, theo filter hiện tại |
| **Bulk Actions** | Checkbox đầu dòng + header "chọn tất cả (trang/toàn bộ)"; Action Bar sticky |
| **Phân trang** | **Server pagination** (mặc định) hoặc infinite scroll (audit/log) |
| **Trạng thái dòng** | hover highlight, selected highlight, row actions menu (⋯) |
| **Sort** | click header; đa cột (Shift+click) tùy chọn |

---

## 6. Wireframes theo màn

### 6.1 Dashboard hệ thống

```
┌ Dashboard hệ thống ──────────────────────────────────────────────────────┐
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌─────────────────┐               │
│ │ 1,248    │ │   7      │ │   41     │ │  312            │               │
│ │ Người dùng│ │ Vai trò │ │ Quyền    │ │ Đăng nhập hôm nay│              │
│ └──────────┘ └──────────┘ └──────────┘ └─────────────────┘               │
│ ┌─────────────────────────────┐ ┌─────────────────────────────────────┐  │
│ │ Top người dùng hoạt động     │ │ ⚠ Log bất thường (24h)             │  │
│ │ 1. Nguyễn A   142 thao tác   │ │ • 5 lần đăng nhập sai — user X      │  │
│ │ 2. Trần B      98            │ │ • Cấp quyền nhạy cảm — role Y       │  │
│ │ 3. ...                       │ │ • Đăng nhập IP lạ — user Z         │  │
│ └─────────────────────────────┘ └─────────────────────────────────────┘  │
│ ┌──────────────────────────────────────────────────────────────────────┐ │
│ │ 🔐 Cảnh báo bảo mật                              [Xem tất cả →]       │ │
│ │ • 2 tài khoản chưa bật xác thực  • 1 role hệ thống vừa đổi quyền      │ │
│ └──────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```
- Mỗi card/widget click → deep-link sang màn tương ứng đã prefilter.
- Biểu đồ dùng ECharts (đã cài).

### 6.2 Vai trò (Roles) + Drawer

```
┌ Vai trò ─────────────────────────────────[+ Tạo vai trò][⋯]──────────────┐
│ [🔎 Tìm] [Trạng thái▾][Sắp xếp▾]                  [💾][⚙ Cột][⬇ Export]   │
│ ┌──────────────────────────────────────────────────────────────────────┐ │
│ │[☑]│ ● Mã        │ Tên           │ Quyền │ User │ Trạng thái │ Cập nhật │ │
│ │[☑]│ 🟣 admin     │ Admin         │  41   │  4   │ ● Active   │ 2 giờ    │ │
│ │[ ]│ 🔵 dispatcher│ Dispatcher    │  18   │ 12   │ ● Active   │ hôm qua  │ │
│ │[ ]│ ⚪ driver    │ Tài xế        │   6   │ 88   │ ○ Locked   │ ...      │ │
│ └──────────────────────────────────────────────────────────────────────┘ │
│ Tổng 7 · Trang 1/1                                                         │
└──────────────────────────────────────────────────────────────────────────┘
  ▶ DRAWER (khi bấm row / Tạo):
  ┌ Sửa vai trò: Dispatcher ───────────────────────────────[✕]┐
  │ Tabs:  [ Thông tin ] [ Quyền ] [ Người dùng ] [ Lịch sử ] │
  │ ── Thông tin ──                                            │
  │ Mã*      [ dispatcher        ] (khóa nếu là role hệ thống) │
  │ Tên*     [ Dispatcher        ]                             │
  │ Mô tả    [ ...                ]                            │
  │ Màu      [● ● ● ● ● ● ● ● ● ● ● ●]  (palette 12 màu)      │
  │ Thứ tự   [  2  ]    Trạng thái [● Active ▾]               │
  │                                                            │
  │ [Clone vai trò]  [Sao chép quyền từ ▾]                     │
  │                                          [Hủy] [Lưu]      │
  └────────────────────────────────────────────────────────────┘
```
- Tab **Lịch sử**: timeline + diff từng thay đổi.
- "⋯ Khác" (header): Thùng rác (khôi phục role đã xóa mềm).

### 6.3 Quyền thao tác (Role × Permission Matrix)

```
┌ Quyền thao tác ──────────────────────[Chế độ: Role×Quyền ▾][⬇ Export]────┐
│ [🔎 Lọc quyền] [Module▾][Loại: CRUD/Workflow▾]      ⚠ 5 thay đổi chưa lưu │
│ ┌──────────────────────┬──────┬──────┬──────┬──────┬──────┬──────────────┐│
│ │ PERMISSION \ ROLE     │admin │dispat│driver│accnt │ dept │ + thêm role  ││
│ ├──────────────────────┼──────┼──────┼──────┼──────┼──────┼──────────────┤│
│ │▼ Nhân sự        [☑col]│  ☑   │  ▣   │  ☐   │  ☐   │  ▣   │              ││
│ │   ├ Xem               │  ☑   │  ☑   │  ☐   │  ☐   │  ☑   │              ││
│ │   ├ Tạo               │  ☑   │  ☐   │  ☐   │  ☐   │  ☑   │              ││
│ │   ├ Sửa               │  ☑   │  ☐   │  ☐   │  ☐   │  ☐   │              ││
│ │   ├ Xóa               │  ☑   │  ☐   │  ☐   │  ☐   │  ☐   │              ││
│ │   ├ Xuất Excel        │  ☑   │  ☑   │  ☐   │  ☑   │  ☐   │              ││
│ │   ├ Import            │  ☑   │  ☐   │  ☐   │  ☐   │  ☐   │              ││
│ │   ├ Duyệt   (workflow)│  ☑   │  ☐   │  ☐   │  ☐   │  ☑   │              ││
│ │   └ Hủy duyệt(workflow)  ☑   │  ☐   │  ☐   │  ☐   │  ☐   │              ││
│ │▶ Kho            [☐col]│  ☐   │  ☐   │  ☐   │  ☐   │  ☐   │              ││
│ │▶ Mua sắm        [▣col]│  ▣   │  ☐   │  ☐   │  ☑   │  ☐   │              ││
│ └──────────────────────┴──────┴──────┴──────┴──────┴──────┴──────────────┘│
│ Chú thích: ☑ đủ · ▣ một phần (indeterminate) · ☐ trống                     │
│                                              [Hủy thay đổi] [Lưu (5)]      │
└──────────────────────────────────────────────────────────────────────────┘
```
- Tick **header module** = chọn cả module cho role đó (cột). Tick **header nhóm** (CRUD/Workflow). Tick **header cột role** = chọn theo filter hiện tại.
- Cột & hàng đầu **sticky** (freeze) khi cuộn.
- Module collapsible, lazy render.
- Ô quyền nhạy cảm: hiện 🔒 + disabled nếu không phải Super Admin.

### 6.4 Gán vai trò (Role Assignment) — Master + Panel

```
┌ Gán vai trò ─────────────────────────────────────────────────────────────┐
│ [🔎 Tìm user][Phòng ban▾][Vai trò▾]      [Gán hàng loạt][Thu hồi hàng loạt]│
│ ┌─────────────────────────────────────────┐ ┌──────────────────────────┐  │
│ │[☑] User           Phòng ban   Vai trò   │ │ Nguyễn Văn A             │  │
│ │[☑] Nguyễn Văn A   Điều vận    🔵🟣      │ │ a@vaschools.edu.vn       │  │
│ │[ ] Trần Thị B     Kế toán     ⚪        │ │ ──────────────────────── │  │
│ │[ ] Lê C           Điều vận    🔵        │ │ Vai trò hiện tại:        │  │
│ │ ...                                     │ │  🔵 Dispatcher  [thu hồi]│  │
│ │                                         │ │  🟣 Admin (đến 30/06) ⏳ │  │
│ │                                         │ │ Vai trò kế thừa:         │  │
│ │                                         │ │  (từ Admin) → ...        │  │
│ │                                         │ │ ──────────────────────── │  │
│ │                                         │ │ Quyền thực tế (41) 🔎    │  │
│ │                                         │ │  • role.view  ⓘ(admin)  │  │
│ │                                         │ │  • trip.assign ⓘ(dispat)│  │
│ │                                         │ │ [+ Gán vai trò]          │  │
│ └─────────────────────────────────────────┘ └──────────────────────────┘  │
│ Action Bar (khi chọn nhiều): [✔ 3 đã chọn] [Gán vai trò][Thu hồi]         │
└──────────────────────────────────────────────────────────────────────────┘
  ▶ DRAWER "Gán vai trò":
   Vai trò* [ chọn 1+ ▾ ]   [✓] Tạm thời  Từ [dd/mm] Đến [dd/mm]
   (gán hàng loạt: hiện "Áp dụng cho 3 user đã chọn")        [Hủy][Gán]
```
- Hover ⓘ ở quyền thực tế → tooltip "Được cấp bởi vai trò: …".
- Role tạm thời có badge ⏳ + ngày hết hạn.

### 6.5 Quản lý menu (Tree trái + Detail phải)

```
┌ Quản lý menu ──────────────────────────────[+ Mục][+ Nhóm][Xem trước]────┐
│ ┌── Cây menu ──────────────┐ ┌── Chi tiết mục ───────────────────────────┐│
│ │ ⠿ ▾ 📁 Tổng quan          │ │ Loại:  ( ) Nhóm   (•) Mục                 ││
│ │ ⠿   • Dashboard           │ │ Nhãn*      [ Yêu cầu điều xe        ]     ││
│ │ ⠿ ▾ 📁 Vận hành          │ │ Icon       [ 🚚 requests        ▾ ]      ││
│ │ ⠿   • Yêu cầu ◄ (chọn)    │ │ Loại đích  (•) Route  ( ) URL ngoài       ││
│ │ ⠿   • Chuyến             │ │ Route      [ /requests             ]     ││
│ │ ⠿   • Chi phí            │ │ Target     [ _self ▾ ]                    ││
│ │ ⠿ ▾ 📁 Hệ thống          │ │ Badge key  [ pending_dispatch ▾ ]         ││
│ │ ⠿   • Vai trò            │ │ Quyền      [ request.view        ▾ ]     ││
│ │ ⠿   • Quyền              │ │ Hiển thị   [● Bật ]    Feature [module.ops]││
│ │   (kéo ⠿ để sắp xếp)     │ │                          [Xóa] [Lưu]     ││
│ └──────────────────────────┘ └───────────────────────────────────────────┘│
│ "Xem trước": mô phỏng sidebar người dùng theo vai trò chọn                 │
└──────────────────────────────────────────────────────────────────────────┘
```
- Drag handle ⠿; drop indicator (đường kẻ) khi kéo; báo lỗi nếu vượt 3 cấp / tạo vòng lặp.
- "Xem trước theo vai trò": chọn 1 role → render menu user sẽ thấy.

### 6.6 Nhật ký hoạt động + Drawer Diff

```
┌ Nhật ký hoạt động ───────────────────────────────────[⬇ Export]──────────┐
│ [📅 30 ngày▾][👤 Người▾][Module▾][Hành động▾][🌐 IP][🔎 Tìm] (sticky)     │
│ ┌──────────────────────────────────────────────────────────────────────┐ │
│ │ Thời gian        Người      Module   Hành động   IP          Kết quả  │ │
│ │ 04/06 10:21:33   Nguyễn A   Roles    role.update 10.0.0.5    ✓        │ │
│ │ 04/06 10:18:02   Trần B     Auth     login       42.x.x.x    ✓        │ │
│ │ 04/06 09:55:10   —          Auth     login.fail  42.x.x.x    ✗        │ │
│ │ ... (infinite scroll)                                                 │ │
│ └──────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
  ▶ DRAWER chi tiết:
  ┌ role.update — Nguyễn A — 04/06 10:21 ───────────────[✕]┐
  │ [ Tổng quan ] [ Thay đổi ]                              │
  │ ── Tổng quan ──                                         │
  │ Người: Nguyễn A   IP: 10.0.0.5                          │
  │ Thiết bị: Windows · Chrome 124   OS: Win 11             │
  │ Module: Roles    Đối tượng: Role#3 (dispatcher)         │
  │ ── Thay đổi (Diff) ──                                   │
  │  Trước                  │  Sau                          │
  │  name: "Dispatcher"     │  name: "Điều phối viên" 🟡    │
  │  permissions: [18]      │  permissions: [20] 🟢+2       │
  │                              [📋 Copy JSON]            │
  └────────────────────────────────────────────────────────┘
```

---

## 7. Responsive Layout

| Breakpoint | Hành vi |
|---|---|
| **≥1280px (Desktop)** | Bố cục đầy đủ: secondary sidebar mở, table nhiều cột, Drawer 480–640px, Assignment 2 cột (list + panel). |
| **768–1279px (Tablet)** | Secondary sidebar thu gọn (icon). Table ẩn bớt cột phụ (ưu tiên cột chính). Assignment: panel quyền chuyển thành Drawer khi chọn user. Matrix cuộn ngang, freeze cột đầu. |
| **<768px (Mobile)** | Chỉ read-only tối thiểu: Dashboard + danh sách (card thay vì table). Thao tác quản trị khuyến nghị dùng Desktop/Tablet (hiển thị banner gợi ý). |

**Quy tắc co bảng**: định nghĩa độ ưu tiên cột (priority 1–3); ẩn dần priority 3 → 2 khi hẹp. Cột priority 1 (định danh + action) luôn hiển thị.

---

## 8. Component Inventory

| Component | Mới/Tái dùng | Ghi chú |
|---|---|---|
| `SystemLayout` (secondary sidebar) | Mới | Wrap 6 màn; breadcrumb |
| `DataTable` (chuẩn mục 5) | **Mới (core)** | Dùng lại cho Roles/Assignment/Audit |
| `AppDrawer` (slide-over) | Mới | Trap focus, ESC, kích thước cấu hình |
| `FilterBuilder` + `SavedFilters` | Mới | Tái dùng `components/filters/*` hiện có làm nền |
| `ColumnSettings` | Mới | Ẩn/hiện + sắp xếp cột; lưu prefs |
| `RoleChip` | Mới | Chip màu role |
| `ColorPalettePicker` | Mới | 12 màu định sẵn |
| `PermissionMatrix` | **Tái dùng + nâng cấp** | Có sẵn `components/system/PermissionMatrix.vue` |
| `PermissionModuleSection` | Tái dùng | Có sẵn |
| `RoleSlideOver` | Tái dùng + nâng cấp | Có sẵn → thêm tabs Lịch sử |
| `UserTable`,`UserRow`,`RoleSelector`,`BulkActionBar` | Tái dùng | `components/user-roles/*` |
| `MenuTree` (drag-drop) | Mới | Cây kéo-thả ≤3 cấp |
| `DiffViewer` | Mới | 2 cột before/after, highlight |
| `StatWidget`, `AnomalyList`, `TopUsers` | Mới | Dashboard widgets (ECharts) |
| `StatusBadge`,`ConfirmModal`,`Button`,`Input`,`Select`,`Card` | Tái dùng | `components/ui/*` |
| `DangerConfirm` (gõ tên/lý do) | Mới | Cho xóa/thu hồi |

---

## 9. Interaction & Motion

- **Drawer**: slide-in từ phải 200ms ease-out; overlay mờ; ESC/overlay đóng (cảnh báo nếu dirty).
- **Optimistic UI**: kéo thả menu, tick matrix — cập nhật ngay, rollback nếu API lỗi.
- **Toast**: thành công 3s auto-dismiss; lỗi giữ + nút "Thử lại".
- **Sticky save bar**: trượt lên từ đáy khi có thay đổi chưa lưu.
- **Skeleton loading** cho table/widget; **không** dùng spinner toàn trang.
- **Inline validation**: lỗi hiện ngay dưới field; nút Lưu disabled khi form invalid.
- **Keyboard**: `/` focus search, `c` mở tạo mới, `Esc` đóng drawer, mũi tên di chuyển dòng (tùy chọn).

## 10. Empty / Loading / Error states

| Trạng thái | Hiển thị |
|---|---|
| Empty (chưa có dữ liệu) | Illustration + 1 dòng mô tả + CTA chính ("Tạo vai trò đầu tiên") |
| Empty (sau filter) | "Không có kết quả" + nút "Xóa bộ lọc" |
| Loading | Skeleton rows/cards |
| Error tải | Banner đỏ + "Thử lại"; giữ filter |
| No permission | Trang 403 thân thiện: "Bạn không có quyền truy cập màn này" |
| Saving | Nút Lưu hiện loading + disable; chặn double submit |
