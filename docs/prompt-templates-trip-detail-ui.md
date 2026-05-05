# Mẫu prompt tối ưu — nâng cấp UI trang chi tiết chuyến

Tài liệu này mô tả cách prompt khi chỉnh giao diện màn **chi tiết chuyến** (điều vận), gắn với file thật trong repo và chia nhỏ công việc vì [`TripDetailView.vue`](../resources/js/src/views/trips/TripDetailView.vue) rất lớn.

## Neo codebase (ghi vào prompt)

| Mục | Chi tiết |
|-----|----------|
| URL production (ví dụ) | `/mng/trips/20` — [trip detail](https://dieuvan.vaschools.edu.vn/mng/trips/20) |
| Route SPA | `trips/:id` trong layout quản lý — [`resources/js/src/router/index.js`](../resources/js/src/router/index.js) |
| View chính | [`resources/js/src/views/trips/TripDetailView.vue`](../resources/js/src/views/trips/TripDetailView.vue) |
| i18n | [`resources/js/src/locales/vi.json`](../resources/js/src/locales/vi.json), `en.json` — prefix thường gặp `trip_detail.*` |
| Rule dự án | [`.cursorrules`](../.cursorrules), [`.cursor/rules/vue.mdc`](../.cursor/rules/vue.mdc) — Tailwind; tránh đổi kiến trúc/API trừ khi cần |

### Component / vùng layout (để chọn scope prompt)

- **Trên cùng / sticky**: `StickyTripHeader` — [`StickyTripHeader.vue`](../resources/js/src/components/trips/StickyTripHeader.vue); meta dòng `created_at`, banner pending trong `TripDetailView.vue`.
- **Cột chính trái (`xl:col-span-7`)**: `TripInfoCard`, `PassengerCheckIn`, `TripTimeline`, `ConflictBanner`, …
- **Cột phải (`xl:col-span-5`)**: khối điều phối (toolbar funnel / coordination), `ResourcePanel`, `DriverCard`, `CostTracker`, `StatusActions`, … — tìm nhanh trong template: `xl:col-span-5`.
- **Chung**: `Card`, `Button`, `Input`, `Select` từ [`resources/js/src/components/ui/`](../resources/js/src/components/ui/).

---

## Bảng chọn vùng (trước khi prompt chi tiết)

Chọn **một** vùng mỗi lượt (hoặc một component cụ thể):

| Vùng | Gợi ý file / anchor |
|------|---------------------|
| Header + sticky + back/CTA | `TripDetailView.vue` (đầu template), `StickyTripHeader.vue` |
| Banner / cảnh báo / link request | `TripDetailView.vue` (khối `dispatch_request` pending, v.v.) |
| Card thông tin chuyến + lộ trình | `TripInfoCard.vue` |
| Hành khách / check-in | `PassengerCheckIn.vue` |
| Cột phải: điều phối + filter funnel | `TripDetailView.vue` (section coordination), rule UI filter [`.cursor/rules/filter-toolbar-vue-tailwind.mdc`](../.cursor/rules/filter-toolbar-vue-tailwind.mdc) |
| Tài xế / xe / chi phí / trạng thái | `DriverCard.vue`, `CostTracker.vue`, `StatusActions.vue`, `ResourcePanel.vue` |

---

## Nguyên tắc prompt “tối ưu”

1. **Mục tiêu + phạm vi**: một vùng hoặc một card mỗi lượt — tránh “làm đẹp cả trang” trong một prompt.
2. **Ràng buộc rõ**: chỉ UI (layout, spacing, typography, màu, responsive); “không đổi API / store / workflow trạng thái chuyến trừ khi bug”.
3. **Tham chiếu thiết kế**: mô tả (vd. “card glass như màn Costs”) hoặc screenshot/Figma; ghi rõ dark mode có/không.
4. **Tiêu chí nhận**: ví dụ “mobile không scroll ngang”, “CTA vẫn thấy khi sticky header”.
5. **Ngôn ngữ**: nhãn mới qua i18n (`vi` + `en`), không hardcode trong template.

---

## Mẫu 1 — Một lượt (khi ý tưởng đã rõ)

```text
Ngữ cảnh: Trang quản lý chi tiết chuyến SPA — route /mng/trips/:id, component
resources/js/src/views/trips/TripDetailView.vue (và component con nó import).

Mục tiêu UI: [vd: làm lại visual hierarchy vùng header + dòng meta (created_at);
tăng contrast banner pending; đồng bộ bo góc/spacing với TripsListView.]

Ràng buộc:
- Chỉ thay đổi template + class Tailwind + (nếu cần) style scoped; không đổi luồng API/store.
- Chuỗi mới cho i18n: thêm key trong resources/js/src/locales/vi.json và en.json.
- Dark mode: [có / không / theo pattern hiện có].

Tiêu chí xong: [3–5 bullet có thể kiểm tra trên desktop + mobile.]

File ưu tiên sửa: [vd: TripDetailView.vue + StickyTripHeader.vue]
```

---

## Mẫu 2 — Chia theo vùng (khuyên dùng với TripDetailView.vue)

```text
Phase — chỉ [Header + StickyTripHeader]:
- Đọc resources/js/src/views/trips/TripDetailView.vue (phần template đầu + import)
  và resources/js/src/components/trips/StickyTripHeader.vue.

Yêu cầu: [mô tả cụ thể: sticky, shadow, breadcrumb, nút primary/secondary…]

Không đụng: TripInfoCard, PassengerCheckIn, cột phải (cost/coordination) — sẽ làm phase sau.

Ràng buộc giống mẫu 1.
```

Lặp prompt tương tự cho **TripInfoCard**, **PassengerCheckIn**, **cột phải** (`CostTracker`, `StatusActions`, `DriverCard`, coordination section), từng phase một.

---

## Mẫu 3 — Đồng bộ design system (ít chữ, đủ ý)

```text
Đồng bộ UI màn trip detail với pattern filter card / violet-teal đã dùng ở màn Costs:
chỉ class Tailwind + cấu trúc div; file TripDetailView.vue và các component trips/*
được import trực tiếp. Không đổi logic. Giữ i18n.
```

---

## Prompt kiểm tra sau mỗi phase (regression)

```text
Sau thay đổi UI vừa rồi: xác nhận loading skeleton, loadError + nút retry, và silentLoadError
ở đầu TripDetailView.vue vẫn hoạt động; responsive sm/xl không vỡ layout. Không đổi hành vi API.
```

---

## Gợi ý thêm

- Chưa có mockup: thêm dòng **“ưu tiên: readability > decoration”** để tránh over-design.
- Không cần chỉnh rule repo chỉ để prompt; prompt đủ **đường dẫn file + scope + ràng buộc + tiêu chí nhận** là tối ưu cho agent.
