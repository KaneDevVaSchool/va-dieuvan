# Cursor Prompt — Redesign màn hình Lịch Sử Chuyến (Driver Mobile)

> Áp dụng: `@.cursorrules` `@.cursor/rules/core.mdc` `@.cursor/rules/laravel.mdc` `@.cursor/rules/vue.mdc`

---

## Mục tiêu

Redesign và bổ sung tính năng cho màn hình **Lịch sử chuyến** (`/driver/trips`) trong ứng dụng mobile của tài xế — thuộc hệ thống **Phần mềm Điều Vận Nội Bộ VA Schools**.

---

## Yêu cầu chức năng cần bổ sung

Dựa trên FRS và SRS hệ thống Điều Vận VA Schools:

### 1. Thống kê nhanh (header widget)
- Hiển thị trong tháng hiện tại: `Tổng chuyến` / `Hoàn thành` / `Đã hủy` / `Tổng km`
- Tất cả nằm trong 1 row ngang, compact, không scroll

### 2. Bộ lọc (filter bar)
Tabs lọc nhanh theo trạng thái:
- `Tất cả` | `Đang thực hiện` | `Hoàn thành` | `Chờ XN` | `Đã hủy`

Dropdown lọc nâng cao (icon funnel):
- Lọc theo **loại chuyến**: D2D / P2P / Công tác / Hàng hóa
- Lọc theo **khoảng ngày** (date range picker)

### 3. Lịch tuần (week calendar strip)
- Thanh ngang T2–CN, highlight ngày hiện tại
- Tap vào ngày → scroll danh sách đến ngày đó
- Hiển thị dot indicator dưới ngày có chuyến

### 4. Danh sách chuyến — trip card (redesign)
Mỗi card hiển thị:
- **Giờ** xuất phát + ngày
- **Badge loại chuyến**: `P2P` / `D2D` / `CT` / `HH` (màu phân biệt)
- **Mã chuyến**: `#36`
- **Badge trạng thái**: `Đang thực hiện` / `Hoàn thành` / `Chờ XN` / `Đã hủy`
- **Điểm đi → điểm đến** (2 dòng, icon dot đầu dòng, màu phân biệt điểm đi/đến)
- **Thông tin phụ**: số khách / học sinh / kiện hàng (icon người hoặc kiện)
- **Km thực tế** (nếu đã hoàn thành)
- Tap vào card → navigate đến `/driver/trips/{id}`

### 5. Trạng thái rỗng (empty state)
- Khi không có chuyến trong ngày/bộ lọc: hiển thị icon + text "Không có chuyến nào"

### 6. Pull-to-refresh
- Kéo xuống để refresh danh sách

---

## Stack & quy ước

- **Frontend**: Vue 3 + Composition API (`<script setup>`)
- **Styling**: Tailwind CSS (mobile-first, dark theme như hiện tại)
- **State**: Pinia store (`useTripStore`)
- **API**: Laravel backend — dùng `useApi()` composable hoặc `axios`
- **Router**: Vue Router — route name `driver.trips.index`
- **i18n**: Không cần, text tiếng Việt trực tiếp
- **Icons**: Heroicons hoặc icon set hiện tại của dự án

---

## Cấu trúc file cần tạo / chỉnh sửa

```
resources/js/
├── pages/driver/
│   └── TripHistory.vue          ← màn hình chính (tạo mới / replace)
├── components/driver/trips/
│   ├── TripCard.vue             ← card chuyến đi (component tách riêng)
│   ├── TripFilterBar.vue        ← tabs + dropdown lọc
│   ├── TripWeekStrip.vue        ← lịch tuần ngang
│   └── TripStatsHeader.vue      ← widget thống kê tháng
├── stores/
│   └── tripStore.js             ← Pinia store (nếu chưa có)
└── composables/
    └── useTripHistory.js        ← logic fetch + filter
```

---

## API endpoints (Laravel)

```
GET /api/driver/trips
  ?status=all|in_progress|completed|pending|cancelled
  ?type=d2d|p2p|business|delivery
  ?date_from=YYYY-MM-DD
  ?date_to=YYYY-MM-DD
  ?month=YYYY-MM              ← cho thống kê header

Response shape:
{
  stats: { total, completed, cancelled, total_km },
  trips: [
    {
      id, code, type, status,
      departure_time, departure_date,
      origin, destination,
      passenger_count,
      actual_km,           // null nếu chưa hoàn thành
      trip_type_label,     // "P2P" | "D2D" | "CT" | "HH"
    }
  ],
  days_with_trips: ["2026-05-06", "2026-05-08"]  // cho dot indicator
}
```

---

## Màu sắc & badge

Dùng đúng design token dark theme hiện tại của app:

| Loại chuyến | Badge class (gợi ý)         |
|-------------|-----------------------------|
| P2P         | `bg-blue-500/20 text-blue-400` |
| D2D         | `bg-purple-500/20 text-purple-400` |
| CT (Công tác) | `bg-amber-500/20 text-amber-400` |
| HH (Hàng hóa) | `bg-orange-500/20 text-orange-400` |

| Trạng thái       | Badge class                         |
|------------------|-------------------------------------|
| Đang thực hiện   | `bg-teal-500/20 text-teal-400`     |
| Hoàn thành       | `bg-green-500/20 text-green-400`   |
| Chờ XN           | `bg-gray-500/20 text-gray-400`     |
| Đã hủy           | `bg-red-500/20 text-red-400`       |

---

## Layout mobile (390px)

```
┌─────────────────────────────────┐
│  ← Lịch sử chuyến           🔍  │  ← header + search
├─────────────────────────────────┤
│  [Tháng này]                    │
│  12 chuyến  10 ✓  1 ✗  430km   │  ← TripStatsHeader
├─────────────────────────────────┤
│  [Tất cả][Đang đi][Hoàn thành] │  ← TripFilterBar (tab scroll ngang)
│  [...][Chờ XN][Đã hủy]    ⚙️   │
├─────────────────────────────────┤
│  T2  T3  T4  T5  T6  T7  CN    │  ← TripWeekStrip
│   4   5  ●6   7  ●8   9  10    │
├─────────────────────────────────┤
│  Thứ Tư, 6/5/2026 (2)          │  ← section header ngày
│  ┌───────────────────────────┐  │
│  │ 17:00  [P2P] #36  [Đang] │  │
│  │ ● Điểm đón               │  │  ← TripCard
│  │ ● Điểm trả               │  │
│  │ 👥 2 khách        8.2km  │  │
│  └───────────────────────────┘  │
│  ┌───────────────────────────┐  │
│  │ 17:00  [D2D] #31  [Đang] │  │
│  │ ...                       │  │
│  └───────────────────────────┘  │
└─────────────────────────────────┘
```

---

## Acceptance criteria

- [ ] Thống kê header load đúng dữ liệu tháng hiện tại
- [ ] Filter tab thay đổi → gọi lại API với param `status` tương ứng
- [ ] Dropdown loại chuyến + date range lọc đúng
- [ ] Lịch tuần highlight đúng ngày hôm nay, dot indicator đúng ngày có chuyến
- [ ] Tap ngày trên week strip → scroll danh sách đến section ngày đó
- [ ] TripCard hiển thị đủ: loại, mã, trạng thái, điểm đi/đến, số khách, km (nếu có)
- [ ] Tap card → navigate `/driver/trips/{id}` (route đã tồn tại hoặc placeholder)
- [ ] Pull-to-refresh hoạt động
- [ ] Empty state hiển thị khi không có data
- [ ] Không break layout ở 375px và 430px
- [ ] Dark theme nhất quán với các màn hình khác của driver app
