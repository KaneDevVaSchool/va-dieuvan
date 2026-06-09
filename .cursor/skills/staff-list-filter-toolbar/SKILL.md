---
name: staff-list-filter-toolbar
description: >-
  Compact production filter toolbar for staff list pages (requests, trips).
  Two-row AppFilterBar, funnel applied-summary + visibility checkboxes,
  row-1 export Excel (CSV) by current filters, icon-only controls.
  Resets bar visibility on mount/activate without clearing applied filters.
  Use when editing RequestsListView, TripsListView, AppFilterBar, list export,
  or filter visibility UX on /mng/* list screens.
---

# Staff list filter toolbar (VA Điều vận)

## Mục tiêu UX

- Thanh lọc **gọn**: hàng 1 điều khiển + xuất; hàng 2 chỉ hiện khi user bật ≥1 checkbox trong phễu.
- **Điều kiện lọc** (query/API) và **hiển thị trên thanh** tách biệt: ẩn ô không xóa giá trị đang lọc.
- Mỗi lần **vào trang** (`onMounted` + `onActivated`, keep-alive): **ẩn hết ô lọc trên thanh** (`resetVisibility()`), **không** reset `filters` / URL.
- **Không** lưu visibility vào `localStorage`.

## Composable

`resources/js/src/composables/useFilterBarVisibility.js`

```js
const { visible, resetVisibility, hasVisibleOnBar } = useFilterBarVisibility(ids, defaults)
```

- `defaults`: mọi id → `false`.
- `hasVisibleOnBar`: `v-if` hàng 2.
- Alias tùy view: ví dụ requests dùng `filterBarVisible` + `hasVisibleBarFilters`.

## Bố cục `AppFilterBar`

### Hàng 1

Container: `flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2`.

| Thứ tự (trái → phải) | Ghi chú |
|----------------------|---------|
| Chọn cột (requests) | `details` + icon `ViewColumnsIcon` + chevron; **không** text; `aria-label` |
| `AppFilterFunnelMenu` | Badge = `activeFilterCount` |
| Divider `hidden sm:block` | |
| Xóa lọc | Icon phễu + `XMarkIcon` góc; `resetFilters` |
| **Xuất Excel (CSV)** | `ml-auto`; requests: `exportRequestsCsv` |

**Cấm trên hàng 1:** chip Định kỳ/Ngoại khóa, nút «Thuộc tính khác», panel lọc phụ trùng phễu.

**Xuất (requests — mẫu chuẩn):**

- Nút hàng 1: `ArrowDownTrayIcon`, nhãn `export_csv` / `export_csv_busy`, `aria-label` = `export_csv_aria`.
- Disabled khi `exportingCsv` hoặc `meta.total === 0`.
- **Theo bộ lọc**, không chỉ trang hiện tại: dùng cùng `buildListParams()` như `reload()`, bỏ `page`/`per_page`, gọi API lặp `page=1..last_page` với `per_page: 100` (max backend `ListRequestsRequest`).
- CSV UTF-8 BOM (`\uFEFF`), `normalizeRequestListParams` qua `listRequests`.
- **Không** đặt nút xuất trên thanh toolbar bảng (sort/bulk giữ riêng).

### Hàng 2

- `v-if="hasVisibleOnBar"` (hoặc alias view), `border-t`, `flex-wrap` các ô lọc.
- `select`: option đầu `value=""` = **tên trường** (nhãn xám khi chưa chọn); có giá trị → `text-slate-900`.
- `AppFilterDropdown`: preset ngày, panel rộng `w-[min(100vw-1.5rem,320px)]` khi cần.
- **Định kỳ / Ngoại khóa:** `select` bình thường (option `1` = bật), không chip hàng 1.
- **Chốt số HS (NK):** `select` khi `filterBarVisible.student_count && filters.extracurricular_only`.
- Chip SLA ngắn khi `sla_risk` visible.

### Panel phễu (slot `AppFilterFunnelMenu`)

1. Tiêu đề + **tóm tắt mọi điều kiện đang áp dụng** (tab, search, từng filter, sort, per_page, định kỳ, NK, …) — kể cả khi ô ẩn trên thanh.
2. «Hiển thị bộ lọc trên thanh» + hint + checkbox `v-model="filterBarVisible[id]"`, id `requests-filter-vis-{id}` / `trips-filter-vis-{id}`.
3. Nút «Xóa tất cả bộ lọc» → `resetFilters(); closeFilterMenu()`.

`activeFilterCount`: chỉ đếm điều kiện API/query, **không** đếm visibility checkbox.

## Requests — visibility ids

`REQUEST_FILTER_BAR_VIS_IDS`: `trip_type`, `depart`, `channel`, `paper`, `priority`, `request_status`, `trip_status`, `sla_risk`, `sort`, `per_page`, `recurring`, `extracurricular`, `student_count`.

`onRequestsFilterBarEnter()` → `resetFilterBarVisibility()` trong `onMounted` + `onActivated`.

## Trips

Cùng pattern hàng 1–2 + phễu; `onTripsFilterBarEnter()`; chưa có xuất CSV — khi thêm, đặt hàng 1 `ml-auto` và tái dùng `buildListParams` + phân trang API trips.

## i18n (`requests_page` / `trips_page`)

- `filter_menu_title`, `filter_show_controls_title`, `filter_show_controls_hint`
- `filter_vis_*` (tên ngắn, không «Chip …»)
- `export_csv`, `export_csv_aria`, `export_csv_busy`, `export_csv_fail` (requests)
- `vi.json` + `en.json` cùng lúc

## Tham chiếu code

| File | Vai trò |
|------|---------|
| `RequestsListView.vue` | Đầy đủ: cột, phễu, xuất, `buildListParams`, `fetchAllFilteredRequestRows` |
| `TripsListView.vue` | Phễu + visibility, chưa export |
| `AppFilterBar.vue`, `AppFilterFunnelMenu.vue`, `AppFilterDropdown.vue` | Vỏ UI |
| `.cursor/rules/staff-list-filter-toolbar.mdc` | Rule agent (glob file liên quan) |

## Checklist màn list staff mới

- [ ] `useFilterBarVisibility`, defaults all `false`
- [ ] `onMounted` + `onActivated` → `resetVisibility()` (+ đóng `details` cột nếu có)
- [ ] Hàng 1: `w-full`, phễu, xóa lọc; xuất `ml-auto` nếu có export
- [ ] Hàng 2 `v-if="hasVisibleOnBar"`
- [ ] Checkbox phễu khớp **mọi** ô hàng 2 (không ô «ẩn» chỉ trong panel riêng)
- [ ] Export dùng **cùng** params list API, phân trang đủ `last_page`
- [ ] i18n `filter_vis_*`, `filter_show_controls_*`, export keys
