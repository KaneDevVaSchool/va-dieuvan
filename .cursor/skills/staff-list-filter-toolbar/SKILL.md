---
name: staff-list-filter-toolbar
description: >-
  Compact production filter toolbar for staff list pages (requests, trips).
  Two-row AppFilterBar, funnel visibility checkboxes, icon-only toolbar actions.
  Resets bar visibility on mount/activate without clearing applied filters.
  Use when editing RequestsListView, TripsListView, AppFilterBar layouts, or
  filter visibility UX on /mng/* list screens.
---

# Staff list filter toolbar (VA Điều vận)

## Mục tiêu UX

- Thanh lọc **gọn**: hàng 1 chỉ icon/chip điều khiển; hàng 2 chỉ hiện khi user bật ít nhất một ô trong phễu.
- **Điều kiện lọc** (query/API) và **hiển thị trên thanh** tách biệt: ẩn ô không xóa giá trị đang lọc.
- Mỗi lần **vào trang** (reload, quay lại từ route khác): **ẩn hết ô lọc trên thanh** (reset visibility), **không** reset `filters` / URL.

## Composable

`resources/js/src/composables/useFilterBarVisibility.js`

```js
const { visible, resetVisibility, hasVisibleOnBar } = useFilterBarVisibility(ids, defaults)
```

- `defaults`: mọi id → `false` (production).
- **Không** lưu visibility vào `localStorage`.
- Gọi `resetVisibility()` trong `onMounted` + `onActivated` (SPA keep-alive).

## Bố cục (Requests / Trips)

1. `AppFilterBar` bọc ngoài.
2. **Hàng 1** (`flex flex-wrap gap-x-1`): chọn cột (icon + chevron), `AppFilterFunnelMenu`, divider, xóa lọc (phễu+X) — không chip định kỳ/ngoại khóa, không «Thuộc tính khác».
3. **Hàng 2** (`v-if="hasVisibleOnBar"`, `border-t`): `select` / `AppFilterDropdown` / chip SLA; định kỳ & ngoại khóa là `select` (option đầu = tên trường, `1` = bật lọc); nhãn field = **option đầu** (`value=""`).

## Panel phễu

1. Tóm tắt điều kiện đang áp dụng.
2. «Hiển thị bộ lọc trên thanh» + checkbox `requests-filter-vis-{id}` / `trips-filter-vis-{id}`.
3. «Xóa tất cả bộ lọc» → `resetFilters()` + đóng phễu.

## Tham chiếu code

- `resources/js/src/views/requests/RequestsListView.vue` — `onRequestsFilterBarEnter`
- `resources/js/src/views/trips/TripsListView.vue` — `onTripsFilterBarEnter`
- `.cursor/rules/staff-list-filter-toolbar.mdc` — rule Cursor (agent áp dụng khi sửa file liên quan)

## Checklist khi thêm màn list mới

- [ ] `useFilterBarVisibility` + defaults all `false`
- [ ] `onMounted` + `onActivated` → `resetVisibility()`
- [ ] Hàng 2 `v-if="hasVisibleOnBar"`
- [ ] Funnel checkbox list khớp mọi ô trên hàng 1–2
- [ ] i18n `filter_vis_*`, `filter_show_controls_*` trong `vi.json` / `en.json`
