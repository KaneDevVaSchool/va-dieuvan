# Module: Chương trình Vận chuyển (Transport Program - TP)

## Mô tả nghiệp vụ

Module TP quản lý vận chuyển học sinh định kỳ theo chương trình (tuần/học kỳ). Khác với module Trip đơn lẻ, TP là một hệ thống phức tạp với:
- Đăng ký học sinh vào chương trình
- Lịch ngày thực hiện tự động
- Điểm danh học sinh mỗi ngày
- Hỗ trợ đa ca (sáng/chiều)
- Import học sinh hàng loạt qua Excel

## Vòng đời chương trình

```
draft → active → (paused) → completed
```

## Vòng đời một ngày thực hiện (ProgramDay)

```
[not_started] → [draft] → [confirmed]
                              ↕ (có thể reopen)
```

## Database liên quan

- `tp_programs` — Chương trình
- `tp_program_days` — Các ngày thực hiện
- `tp_students` — Học sinh
- `tp_enrollments` — Đăng ký học sinh vào chương trình
- `tp_day_absences` — Vắng mặt theo ngày
- `tp_trip_executions` — Thực thi chuyến (1 hoặc 2 ca/ngày)
- `tp_trip_student_logs` — Log từng học sinh trong execution
- `tp_import_batches` — Lô import
- `tp_import_rows` — Từng dòng import
- `tp_audit_logs` — Log nghiệp vụ TP
- `tp_absence_reasons` — Bộ lý do vắng chuẩn

## API Endpoints

### Program Management
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/transport-programs` | Danh sách chương trình |
| POST | `/api/transport-programs` | Tạo chương trình |
| GET | `/api/transport-programs/{id}` | Chi tiết |
| PUT | `/api/transport-programs/{id}` | Cập nhật |
| POST | `/api/transport-programs/{id}/activate` | Kích hoạt |
| POST | `/api/transport-programs/{id}/pause` | Tạm dừng |
| POST | `/api/transport-programs/{id}/complete` | Kết thúc |

### Program Days
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/tp-program-days` | Danh sách ngày |
| GET | `/api/tp-program-days/{id}` | Chi tiết ngày |
| PUT | `/api/tp-program-days/{id}/driver` | Gán tài xế cho ngày |

### Attendance
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/tp-attendance/{dayId}` | Xem điểm danh |
| POST | `/api/tp-attendance/{dayId}/absent` | Đánh vắng 1 học sinh |
| POST | `/api/tp-attendance/{dayId}/absent-bulk` | Đánh vắng nhiều |
| POST | `/api/tp-attendance/{dayId}/present` | Hủy vắng |
| POST | `/api/tp-attendance/{dayId}/all-present` | Tất cả có mặt |
| POST | `/api/tp-attendance/{dayId}/draft` | Lưu nháp |
| POST | `/api/tp-attendance/{dayId}/confirm` | Xác nhận điểm danh |
| POST | `/api/tp-attendance/{dayId}/reopen` | Mở lại điểm danh |

### Students & Enrollment
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/tp-students` | Danh sách học sinh |
| POST | `/api/tp-programs/{id}/enroll` | Đăng ký học sinh |
| DELETE | `/api/tp-programs/{id}/enroll/{studentId}` | Hủy đăng ký |

### Import
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/api/trips/tp-import` | Upload Excel |
| GET | `/api/trips/tp-import/{id}` | Trạng thái import |
| POST | `/api/trips/tp-import/{id}/mapping` | Cấu hình mapping |
| POST | `/api/trips/tp-import/{id}/execute` | Thực thi import |
| POST | `/api/trips/tp-import/{id}/fix` | Tự động fix lỗi |
| GET | `/api/trips/tp-import/{id}/error-report` | Báo cáo lỗi |

### Driver (TP-specific)
| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/api/driver/tp-days` | Danh sách ngày (tài xế) |
| GET | `/api/driver/tp-days/{id}` | Chi tiết ngày |
| POST | `/api/driver/tp-days/{id}/confirm` | Xác nhận chuyến |
| POST | `/api/driver/tp-days/{id}/start` | Bắt đầu chuyến |
| POST | `/api/driver/tp-days/{id}/sync` | Sync offline data |
| POST | `/api/driver/tp-days/{id}/complete` | Kết thúc chuyến |
| POST | `/api/driver/tp-days/{id}/students/{studentId}/board` | Check-in học sinh |
| POST | `/api/driver/tp-days/{id}/students/{studentId}/absent` | Đánh vắng từ tài xế |

## Permission

- `tp.view` — Xem chương trình
- `tp.manage` — Quản lý chương trình
- `tp.attendance.view` — Xem điểm danh
- `tp.attendance.edit` — Sửa điểm danh
- `tp.attendance.confirm` — Xác nhận điểm danh
- `tp.import` — Import học sinh
- `tp.students.manage` — Quản lý học sinh

## Business Rules

1. **Optimistic locking điểm danh**: `attendance_lock_version` chống xung đột khi nhiều người cùng thao tác
2. **Shift support**: Hỗ trợ cả "sáng" và "chiều" với các cột riêng (`morning_*`, `afternoon_*`)
3. **Auto-sync execution log**: Khi đánh vắng qua dispatcher, tự động cập nhật `TpTripStudentLog` nếu chuyến đang chạy
4. **Missing reason block**: Không cho phép xác nhận điểm danh nếu còn học sinh vắng chưa có lý do
5. **Enrollment date-aware**: Điểm danh chỉ tính học sinh `enrolled_at <= day.date` và `unenrolled_at > day.date`
6. **Import validation**: Kiểm tra student code trùng, trường bắt buộc, format ngày tháng

## Known Issues

1. **markAbsentBulk() gọi markAbsent() trong loop**: Mỗi học sinh 1 transaction riêng → không atomic cho toàn batch
2. **boardedAtMapForDay() không eager load**: Gọi `$execution->studentLogs()->get()` thay vì eager load từ trước
3. **Dynamic column names trong sessionState()**: `$day->{$statusCol}` dùng dynamic property access — khó type check
