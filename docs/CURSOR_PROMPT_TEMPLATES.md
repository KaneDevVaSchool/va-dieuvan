# Cursor — Prompt mẫu (rules + docs)

File này tập hợp prompt **copy-paste** cho Cursor Agent, bám [.cursorrules](../.cursorrules), [.cursor/rules/](../.cursor/rules/), và [docs/](./).

---

## 1. Prompt ngắn (mặc định)

```
Dự án: VA Điều Vận (Laravel 10 API + Vue 3 SPA, Sanctum, Spatie permission).

Nhiệm vụ: [mô tả 1–3 câu, kết quả mong đợi]

Phạm vi:
- Chỉ sửa: @[đường dẫn file hoặc thư mục]
- Không refactor lan; không đổi kiến trúc trừ khi tôi yêu cầu.

Stack:
- Backend: Controller mỏng → FormRequest → Service; có thể ApiResponses; middleware dispatch.web / dispatch.staff / driver.spa / permission / idempotency theo routes/api/spa.
- Frontend: Component → Store/composable → resources/js/src/api/* (không axios trực tiếp trong .vue nếu tránh được); useAuthStore: hasPermission, hasAnyPermission, isFeatureEnabled, isNavFeatureVisible; i18n trong resources/js/src/locales.

Tra cứu khi cần (đọc có chừng mực):
- docs/FEATURES_AND_MODULES.md + docs/API_OVERVIEW.md nếu đụng API/route
- docs/DATABASE_SPECIFICATION.md nếu đụng migration/model
- docs/PERMISSION_AND_ROLE.md nếu đụng quyền
- docs/QUEUE_EVENT_CRON.md nếu đụng notification/command

Output: diff/code cần thiết + giải thích ngắn tiếng Việt (root cause nếu là bug).
```

---

## 2. Prompt đầy đủ (feature / API mới)

```
Context
- Repo: va-dieuvan — modular monolith; API SPA trong routes/api/spa/*.php
  (common-read|mutate, driver-read|mutate, dispatch-staff-read|mutate).
- Auth: Bearer Sanctum; Google OAuth ở routes/web.php (không nhầm với /api).

Task
[Mô tả feature: endpoint, field, màn hình, luồng nghiệp vụ]

Constraints
- Thay đổi tối thiểu; không xóa comment/code không liên quan.
- POST nhạy cảm: kiểm tra có cần middleware idempotency + route name như các endpoint hiện có (xem docs/API_OVERVIEW.md).
- Trip / gán chuyến: nhớ lock_version và optimistic lock nếu chạm logic kiểu DispatchingService.
- Permission: FormRequest authorize + cập nhật database/seeders/RbacSeeder.php nếu thêm permission mới; đồng bộ docs/PERMISSION_AND_ROLE.md nếu team quy ước cập nhật doc.
- Vue: meta.permission / meta.featureKey trên router khi thêm màn staff; meta.driverApp cho shell tài xế.

Scope (đính kèm @)
@[routes/api/spa/...]
@[app/Http/Controllers/Api/...]
@[app/Http/Requests/...]
@[resources/js/src/...]

Docs (mở đúng mục, không đọc cả file)
- docs/API_OVERVIEW.md
- docs/FEATURES_AND_MODULES.md
- docs/DATABASE_SPECIFICATION.md (nếu đổi schema)

Deliverables
1) Code đúng convention repo
2) Tóm tắt thay đổi + endpoint mới (method, path, middleware) nếu có
```

---

## 3. Prompt sửa bug

```
Chế độ: chỉ sửa bug — scope tối thiểu (.cursor/rules/core.mdc).

Triệu chứng: [lỗi UI/API/log, HTTP status]

Mong đợi: [hành vi đúng]

Scope: @[file hoặc file:start-end]

Không: refactor nhánh khác, đổi kiến trúc, file không liên quan.

Sau khi sửa (bắt buộc):
1) Root cause — tiếng Việt, ngắn
2) Vì sao fix này đúng

Docs gợi ý: docs/TROUBLESHOOTING.md hoặc docs/FEATURES_AND_MODULES.md (module liên quan).
```

---

## 4. Prompt xử lý feedback (PR / code review)

```
Chế độ: xử lý feedback review — thay đổi tối thiểu, đúng chỗ reviewer chỉ ra.

Feedback (copy nguyên hoặc liệt kê):
1) [Comment 1 — file/dòng nếu có]
2) [Comment 2]
...

Scope:
- Chỉ sửa phần liên quan từng comment; @ các file reviewer đề cập.
- Nếu feedback mơ hồ: làm đúng ý an toàn nhất; hỏi lại trong reply nếu không chắc.

Quy tắc repo:
- Laravel: FormRequest + Service; không nhồi logic nặng vào controller.
- Vue: Store/API layer; khớp permission/feature toggle nếu đụng UI ẩn-hiện.
- Không “fix” bằng cách disable validation hoặc bỏ authorize trừ khi feedback yêu cầu rõ.

Output:
1) Diff / từng thay đổi map với từng comment (1→…, 2→…)
2) Nếu không làm được comment nào: nêu lý do + đề xuất thay thế
```

---

## 5. Prompt xử lý feedback QA / UAT

```
Chế độ: fix theo báo cáo QA — ưu tiên đúng spec nghiệp vụ và docs.

Báo cáo QA:
- Môi trường: [staging/local, branch]
- Bước tái hiện: [1…2…3]
- Actual: [quan sát]
- Expected: [theo BA/spec hoặc docs/FEATURES_AND_MODULES.md]

Scope: @[màn hình / API / file liên quan]

Yêu cầu:
- Xác định là bug UI, bug API, hay sai spec; nếu sai spec thì chỉnh code cho khớp docs đã thống nhất hoặc ghi rõ cần làm rõ BA.
- Kiểm tra permission (docs/PERMISSION_AND_ROLE.md) và feature toggle nếu QA báo “không thấy nút / 403”.
- Không regression: nêu rủi ro và chỗ cần test lại.

Output: fix + checklist test ngắn cho QA.
```

---

## 6. Prompt chỉ định rule Cursor (tùy chọn)

```
Áp dụng thêm (nếu Agent chưa auto-load đủ):
- .cursor/rules/domain.mdc — luôn có ngữ cảnh domain
- .cursor/rules/api-routes.mdc — khi sửa routes/**/*.php
- .cursor/rules/database.mdc — khi sửa database/**/*.php
- .cursor/rules/permissions-rbac.mdc — khi sửa Middleware / FormRequest / Api/Admin
- .cursor/rules/notifications-queue.mdc — khi sửa Notifications / Listeners / Console
- .cursor/rules/vue.mdc + filter-toolbar-vue-tailwind.mdc — khi sửa Vue + thanh lọc

Task: […]
```

---

## Liên quan

- [PROJECT_OVERVIEW.md](./PROJECT_OVERVIEW.md)
- [API_OVERVIEW.md](./API_OVERVIEW.md)
- [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

