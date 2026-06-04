# 03 — Edge Cases & Quyết định đã chốt

> Mỗi case được ghi rõ: tình huống, vấn đề phát sinh, quyết định chọn, và lý do.

---

## Nhóm 1: Program & Day Generation

### EC-01 — Thay đổi end_date sau khi đã có data

**Tình huống:** Admin tạo chương trình 01/06 → 30/06, đã có 10 ngày vận hành với absences và executions. Admin muốn kéo dài đến 15/07.

**Vấn đề:** Cần sinh thêm ngày 01/07 → 15/07, nhưng không được xóa/ảnh hưởng 10 ngày cũ.

**Quyết định:** `ProgramDayGeneratorService::regenerate()` chỉ INSERT ngày mới, không xóa ngày cũ.

```
newSet = {01/06..15/07}
currentSet = {01/06..30/06}
days_to_add = {01/07..15/07} → INSERT
days_to_remove = {} (empty)
```

---

### EC-02 — Rút ngắn end_date khi đã có executions

**Tình huống:** Chương trình chạy đến 30/06, admin rút xuống còn 20/06. Ngày 21/06 → 30/06 đã có absences nhưng chưa có execution.

**Vấn đề:** Xóa các ngày thừa sẽ mất absences, không xóa thì data rác.

**Quyết định:** Kiểm tra per-day:
- Ngày có `day_absences` hoặc `trip_executions` → SET `day_type = 'cancelled'` (không xóa, giữ data)
- Ngày trống (không có data gì) → DELETE

```php
$daysToRemove->each(function ($day) {
    $hasData = $day->absences()->exists() || $day->execution()->exists();
    $hasData ? $day->update(['day_type' => 'cancelled']) : $day->delete();
});
```

---

### EC-03 — Tạo chương trình với date range dài (1 năm học)

**Tình huống:** 01/09/2026 → 31/05/2027 với runs_on Mon-Fri = ~270 ngày.

**Vấn đề:** INSERT 270 rows trong một request.

**Quyết định:** Dùng `UPSERT` chunked (batch 100 rows), chạy sync trong request. 270 rows với chunked upsert < 200ms trên PostgreSQL — chấp nhận được.

```php
$dates->chunk(100)->each(fn($chunk) =>
    TpProgramDay::upsert($chunk->toArray(), ['program_id', 'scheduled_date'], ['day_type'])
);
```

**Không dùng queue/job** — sync tại request cho phép response trả về day_count ngay lập tức cho UI.

---

### EC-04 — Thay đổi `runs_on` sau khi chương trình đã active

**Tình huống:** Chương trình runs Mon-Fri, admin muốn bỏ thứ 6.

**Vấn đề:** Các ngày thứ 6 trong tương lai đã được generate. Xử lý thế nào?

**Quyết định:**
- Ngày thứ 6 trong **quá khứ** (đã có execution hoặc absences): SET `day_type = 'cancelled'`
- Ngày thứ 6 trong **tương lai** không có data: DELETE
- Ngày thứ 6 trong tương lai có absences pre-recorded: SET `day_type = 'cancelled'`, notify dispatcher

**UI warning:** "Thay đổi này sẽ hủy X ngày thứ Sáu chưa thực hiện."

---

### EC-05 — `extra_dates` rơi vào ngày trong `excluded_dates`

**Tình huống:** Admin add `extra_dates = ['2026-06-15']` nhưng `excluded_dates = ['2026-06-15']`.

**Vấn đề:** Conflict giữa hai settings.

**Quyết định:** `excluded_dates` thắng — ngày trong cả hai sẽ không được sinh. Validation warning khi lưu: "Ngày 15/06 vừa trong Extra vừa trong Excluded — sẽ không được sinh."

---

### EC-06 — Chương trình `paused` trong giữa date range

**Tình huống:** Chương trình 01/06 → 30/06, pause vào ngày 10/06. Resume vào 15/06.

**Vấn đề:** Các ngày 10/06 → 14/06 đã được generate. Có tự động cancel không?

**Quyết định:** Pause/Resume **không tự động** thay đổi `day_type` của existing days. Dispatcher tự cancel từng ngày thủ công nếu cần. Lý do: pause có thể ngắn hạn (1-2 ngày) và dispatcher biết rõ ngày nào thực sự không chạy.

---

## Nhóm 2: Driver Assignment

### EC-07 — Driver assigned per-day bị xóa khỏi hệ thống

**Tình huống:** Driver A được gán override cho ngày 10/06. Sau đó Driver A bị deactivate.

**Vấn đề:** `tp_program_days.driver_id` trỏ đến driver không còn active.

**Quyết định:**
- FK giữ nguyên (không cascade delete driver)
- Khi resolve effective driver: kiểm tra `driver.status = 'active'`
- Nếu driver deactivated: effective_driver = NULL → treat như chưa gán
- Alert dispatcher: "Driver A đã bị vô hiệu hóa — X ngày cần gán lại"

---

### EC-08 — Default driver của program bị thay đổi

**Tình huống:** Chương trình có default_driver = A. Admin đổi sang B. Ngày 10/06 đã có execution của A.

**Vấn đề:** Thay đổi default không được ảnh hưởng execution đã completed.

**Quyết định:**
- `tp_trip_executions` lưu `driver_snapshot` (immutable JSON) — không bị ảnh hưởng
- Các ngày đã có `trip_executions`: hiển thị snapshot driver, không cần resolve lại
- Các ngày chưa có execution: resolve lại theo default mới
- Không retroactively thay đổi bất cứ thứ gì

---

### EC-09 — Conflict check driver: overlap time vs exact match

**Tình huống:** Chương trình A: 06:00-07:00. Chương trình B: 06:30-07:30. Cùng driver.

**Vấn đề:** Overlap thời gian nhưng không cùng giờ chính xác.

**Quyết định:** Conflict nếu `|timeA - timeB| < 90 phút` (configurable). Không cần exact match — xe không thể tele-transport. Default threshold = 90 phút. Lưu trong `settings` của program hoặc school_settings.

---

### EC-10 — Driver muốn xem lịch của mình nhiều ngày tới

**Tình huống:** Driver muốn biết tuần tới có chuyến gì.

**Vấn đề:** `GET /driver/tp-days` hiện tại chỉ trả hôm nay.

**Quyết định:** Cho phép query `?date_from=&date_to=` tối đa 7 ngày. Driver chỉ thấy ngày của mình (effective driver match). Không cho phép query quá xa — tránh data stale (assignment có thể thay đổi).

---

## Nhóm 3: Execution & Attendance

### EC-11 — Driver bấm "Bắt đầu" nhưng không có enrollment nào

**Tình huống:** Tất cả học sinh đã bị unenroll hoặc chương trình không có học sinh.

**Vấn đề:** Tạo execution với 0 student logs.

**Quyết định:** Cho phép tạo execution với 0 logs. Có thể xảy ra hợp lệ (học sinh đã được transfer hết). Driver hoàn thành ngay (không có pending nào). Log audit ghi rõ `total_expected = 0`.

---

### EC-12 — Dispatcher đánh vắng sau khi driver đã start

**Tình huống:** Driver đã start lúc 06:00. Dispatcher đánh vắng học sinh A lúc 06:05.

**Vấn đề:** `tp_day_absences` được tạo, nhưng `tp_trip_student_logs` cho học sinh A đã có `final_status = 'pending'` (chưa board).

**Quyết định:**
- INSERT `tp_day_absences` bình thường
- Realtime update `tp_trip_student_logs` nếu execution đang `in_progress`:
  - UPDATE `final_status = 'absent'`, `initial_status = 'pre_absent'`, `absence_type = 'parent_notified'`
  - Decrement `total_expected`
- SSE push xuống app driver: học sinh A vừa được đánh vắng bởi dispatcher
- Driver thấy học sinh A chuyển sang "Vắng có phép" — không cần tích nữa

---

### EC-13 — Driver đánh vắng, sau đó học sinh xuất hiện

**Tình huống:** Driver mark học sinh B absent (no_notice). Học sinh B đuổi kịp xe ở điểm sau.

**Vấn đề:** `final_status = 'absent'` không thể đổi sang boarded theo flow bình thường.

**Quyết định:** Cho phép "Undo absent" → SET `final_status = 'pending'` → Driver tích board bình thường. Thêm nút "Hoàn tác vắng" trong driver app (chỉ khi execution `in_progress`). Ghi audit: `log.absence_undone`.

---

### EC-14 — Offline sync: thứ tự actions conflict

**Tình huống:** Driver offline. Action queue: `board(A)`, `absent(A, no_notice)`. Cả hai cho cùng học sinh A.

**Vấn đề:** Không thể board và absent cùng một học sinh.

**Quyết định:** Khi sync, xử lý theo `client_timestamp`. Action sau cùng (absent) sẽ override action trước (board). Log ghi cả hai actions. Dispatcher được notify về conflict sequence.

---

### EC-15 — Execution đang in_progress nhưng driver mất app access

**Tình huống:** Driver bị revoke account/role trong khi đang chạy chuyến.

**Vấn đề:** Execution stuck ở `in_progress`, không ai complete được.

**Quyết định:** Dispatcher có quyền force-complete execution từ web portal. Action: `POST /api/tp-executions/:id/force-complete` (admin/dispatcher only). Ghi audit: `execution.force_completed_by {actor}`. Học sinh còn pending → auto-mark absent với note "Force completed by dispatcher".

---

### EC-16 — `expected_count` vs actual enrolled count không khớp

**Tình huống:** `expected_count` được denormalize trên `tp_program_days`. Sau nhiều enroll/unenroll/absence operations, count có thể drift.

**Vấn đề:** Denormalized counter có thể out-of-sync.

**Quyết định:**
- Tất cả operations (enroll, unenroll, mark_absent, unmark_absent) phải UPDATE `expected_count` trong cùng transaction
- Thêm nightly reconciliation job (nhẹ, chỉ đọc): compare `expected_count` với actual query, log discrepancy nếu có
- Admin có thể trigger manual recalculate: `POST /api/tp-programs/:id/recalculate-counts`

---

## Nhóm 4: Import

### EC-17 — Import file có row trùng code với student đã tồn tại

**Tình huống:** Import 100 học sinh, 10 em đã có trong `tp_students` với cùng `code`.

**Vấn đề:** Duplicate hay update?

**Quyết định:** Mặc định là **skip** (warning, không error) — không overwrite data hiện có. Tùy chọn trong import review: "Cập nhật thông tin nếu đã tồn tại" → update mode. Import mode được ghi vào `tp_import_batches.settings`.

---

### EC-18 — File Excel nhiều sheet

**Tình huống:** File xlsx có 3 sheets: "Khối 1", "Khối 2", "Khối 3".

**Vấn đề:** Parser đọc sheet nào?

**Quyết định:** Mặc định đọc sheet đầu tiên. Trong upload step, show dropdown để user chọn sheet nếu file có > 1 sheet.

---

### EC-19 — Import bị interrupt giữa chừng (server restart)

**Tình huống:** `tp_import_batches.status = 'importing'`, server crash. Rows một phần đã import.

**Vấn đề:** Không biết đã import đến đâu.

**Quyết định:**
- `tp_import_rows.import_status` ghi per-row — có thể resume từ `import_status = 'pending'`
- Resume: `POST /api/tp-imports/:id/execute` khi status = 'importing' → tiếp tục từ pending rows
- Nếu detect `status = 'importing'` lâu hơn 10 phút → auto-set `status = 'failed'`, notify importer

---

### EC-20 — Column mapping không khớp (header không đoán được)

**Tình huống:** File Excel header: "HỌ VÀ TÊN ĐẦY ĐỦ", "MÃ SỐ HỌC SINH", "LỚP HỌC".

**Vấn đề:** Auto-suggest fuzzy match có thể sai.

**Quyết định:**
- Auto-suggest nhưng không auto-apply — user phải confirm từng field
- Highlight mapping không chắc chắn (confidence < 70%) với màu vàng
- Nếu user không map "full_name" → block proceed với error "Trường Họ tên là bắt buộc"
- Lưu mapping profile: nếu cùng header pattern đã dùng trước → suggest lại lần sau (localStorage hoặc user_settings)

---

## Nhóm 5: Migration từ hệ thống cũ

### EC-21 — Học sinh trong `student_policies` không có record trong `students`

**Tình huống:** Một số `student_policies.student_id` trỏ đến `students` đã bị xóa cứng (không soft delete).

**Vấn đề:** JOIN không ra kết quả.

**Quyết định:** Migration script skip các records mồ côi, ghi vào migration error log. Sau migration, report cho admin để xử lý thủ công.

---

### EC-22 — Cùng học sinh trong nhiều `student_policies` (nhiều tuyến)

**Tình huống:** Học sinh A có 2 policies: tuyến X sáng + tuyến Y chiều.

**Vấn đề:** Trong thiết kế mới, học sinh là entity độc lập, enroll riêng vào từng program.

**Quyết định:** Migration tạo 1 `tp_students` record. Tạo 2 `tp_enrollments` (một cho program X, một cho program Y). Không merge — giữ đúng semantics.

---

### EC-23 — `policy_trips` có status = 'in_progress' khi migrate

**Tình huống:** Migration chạy trong giờ vận hành, có chuyến đang chạy.

**Vấn đề:** Migrate chuyến đang live.

**Quyết định:** Migration chỉ chạy cho historical data (trips đã `completed` hoặc `cancelled`). Trips đang `in_progress` hoặc `scheduled`/`assigned` trong ngày hiện tại → giữ trên hệ thống cũ cho đến khi hoàn thành. Migration cutover được thực hiện sau giờ cao điểm (tối hoặc cuối tuần).

---

## Nhóm 6: Business Logic đặc thù

### EC-24 — Học sinh tham gia nhiều chương trình cùng lúc

**Tình huống:** Học sinh A enroll vào cả "Đưa đón sáng" và "Đưa đón chiều" (2 programs khác nhau).

**Vấn đề:** Có conflict không?

**Quyết định:** Không block — đây là use case hợp lệ (sáng đi, chiều về). `tp_enrollments` UNIQUE constraint chỉ ngăn enroll 2 lần vào cùng 1 program. Cross-program enrollment không có constraint.

---

### EC-25 — Program không có `default_driver_id` và ngày không có override

**Tình huống:** Program mới tạo, chưa set default driver. Dispatcher chưa gán per-day.

**Vấn đề:** `effective_driver = NULL`. Driver nào thấy chuyến này?

**Quyết định:**
- Chuyến không hiển thị trên bất kỳ driver app nào (không có ai được giao)
- Dashboard dispatcher: badge ⚠ "Chưa có tài xế" màu đỏ
- Alert tự động: nếu ngày X đang `operating` và đến 2h trước giờ xe mà chưa có effective driver → push notification đến dispatcher

---

### EC-26 — `cost_per_trip = NULL` (chương trình miễn phí / chưa config)

**Tình huống:** Chương trình đưa đón học sinh chính sách (miễn phí). Không set cost.

**Vấn đề:** `tp_trip_executions.estimated_cost = NULL`. Báo cáo cost bị blank.

**Quyết định:** NULL = không áp dụng chi phí. Báo cáo cost hiển thị "—" thay vì 0. Kế toán vẫn có thể nhập `actual_cost` sau nếu cần tracking nội bộ. Không force required.

---

### EC-27 — Bulk unenroll học sinh khi chương trình đã `completed`

**Tình huống:** Chương trình đã completed (end_date qua). Admin muốn unenroll toàn bộ học sinh (cleanup).

**Vấn đề:** Có nên cho phép không? Dữ liệu lịch sử có bị ảnh hưởng?

**Quyết định:** Cho phép unenroll (set `unenrolled_at`) ngay cả trên program đã completed — đây là administrative cleanup, không ảnh hưởng lịch sử (executions và logs đã completed giữ nguyên). Tuy nhiên, không cho phép DELETE enrollment.

---

### EC-28 — Ngày `makeup` (học bù) rơi vào thứ 7/CN

**Tình huống:** Trường học bù vào thứ 7. `runs_on` hiện tại không include "sat".

**Vấn đề:** Ngày thứ 7 đó không được auto-generate vì không trong `runs_on`.

**Quyết định:** Dùng `extra_dates` để manually thêm ngày học bù bất kể `runs_on`. Admin thêm ngày vào `extra_dates`, sau đó system sinh thêm day với `day_type = 'makeup'`. UI có button "Thêm ngày học bù" trong Schedule tab.

---

### EC-29 — Học sinh unenroll nhưng đã có `tp_day_absences` trong tương lai

**Tình huống:** Học sinh A bị unenroll ngày hôm nay. Tuần tới đã có pre-recorded absence cho A.

**Vấn đề:** Absence record trở thành orphan data (học sinh không còn trong program).

**Quyết định:** Giữ absence records — không cascade delete. Khi load attendance, chỉ show học sinh còn enrolled tính đến ngày đó. Absence records cho học sinh đã unenroll sẽ không hiển thị trong day attendance view, nhưng vẫn query được qua audit log. Lý do: có thể re-enroll sau, và giữ data integrity cho lịch sử.

---

### EC-30 — `tp_trip_student_logs.client_timestamp` sai giờ thiết bị

**Tình huống:** Driver device có giờ sai lệch ±30 phút so với server time.

**Vấn đề:** Log timestamp không tin cậy.

**Quyết định:**
- Luôn lưu cả `client_timestamp` và `server timestamp` (created_at)
- Report và analytics dùng `created_at` (server) làm authoritative timestamp
- `client_timestamp` chỉ dùng để sort offline actions khi sync — không dùng để display
- Nếu `|client_timestamp - server NOW()| > 30 phút` → flag `sync_status = 'timestamp_suspect'`, log warning
