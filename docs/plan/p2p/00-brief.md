# 00 — Brief: Redesign Module Đưa đón Học sinh

---

## 1. Bối cảnh

Hệ thống hiện tại (`policy_trips`) được xây dựng để tự động sinh chuyến xe mỗi ngày cho nhóm học sinh chính sách. Sau một thời gian vận hành, xuất hiện khoảng cách lớn giữa **cách hệ thống nghĩ** và **cách người dùng thực sự làm việc**.

### 1.1 Hệ thống hiện tại hoạt động như thế nào

```
student_policies
    → Cron Job 22:00
        → Auto Generate policy_trips
            → Điều vận gán driver
                → Driver điểm danh
                    → Complete trip
```

Toàn bộ nghiệp vụ xoay quanh **chuyến xe** (`policy_trips`).

### 1.2 Người dùng thực sự quản lý gì

Khi phỏng vấn điều vận và admin:

> *"Tôi không quản lý chuyến xe. Tôi quản lý chương trình đưa đón — học sinh nào tham gia, ngày nào chạy, hôm nay ai vắng."*

Người dùng đang nghĩ theo **Chương trình → Học sinh → Lịch ngày**, không phải **Tuyến → Chuyến → Điểm danh**.

---

## 2. Vấn đề cụ thể của hệ thống cũ

### P1 — Cron job là single point of failure

Nếu job 22:00 fail, fallback 05:00 cùng ngày. Nếu cả 2 fail → không có chuyến, phải tạo thủ công. Hệ thống phụ thuộc vào scheduler để hoạt động bình thường.

### P2 — Không có khái niệm "Chương trình"

`student_policies` là mapping học sinh → tuyến, không phải entity quản lý được. Không có tên, mô tả, ngày bắt đầu/kết thúc rõ ràng ở cấp chương trình.

### P3 — Attendance lưu dày (dense)

Mỗi học sinh được INSERT vào `policy_trip_students` cho mỗi chuyến, dù chỉ cần biết "ai vắng hôm nay". 500 học sinh × 270 ngày = 135,000 rows không cần thiết.

### P4 — Plan và Runtime gộp vào một bảng

`policy_trips` vừa là kế hoạch (date, route, time_slot) vừa là runtime (actual_departure, boarded_count). Dispatcher và driver đều thao tác trên cùng bảng → khó tách permission, khó audit rõ ràng.

### P5 — Driver assignment là blocking step

Chuyến không xuất hiện trên app driver nếu chưa assign. Không có cơ chế default driver theo tuyến — mỗi ngày điều vận phải gán lại.

### P6 — Không có enterprise import

Thêm học sinh phải tạo thủ công từng em. Không có pipeline import Excel chuẩn.

### P7 — Schema cũ không thể mở rộng

Muốn thêm cost tracking, muốn assign nhiều chuyến trong ngày, muốn học sinh tham gia nhiều chương trình → phải đụng `policy_trips` và `student_policies`, phá vỡ constraint hiện tại.

---

## 3. Mục tiêu Redesign

### Goal 1 — Đổi tư duy từ Trip-centric sang Program-centric

| Cũ | Mới |
|----|-----|
| Quản lý chuyến xe | Quản lý chương trình đưa đón |
| Sinh chuyến hàng ngày | Sinh lịch khi tạo chương trình |
| Học sinh trong chuyến | Học sinh trong chương trình |
| Điều vận xem chuyến | Điều vận xem ngày vận hành |

### Goal 2 — Tách biệt Plan và Runtime

```
Dispatcher quản lý:          Driver thực thi:
tp_programs                  tp_trip_executions
tp_program_days        →     tp_trip_student_logs
tp_enrollments
tp_day_absences
```

### Goal 3 — Driver Layer độc lập, không blocking

- Program có `default_driver_id` — tài xế cố định theo chương trình
- Từng ngày có thể override nếu cần
- Driver vẫn thấy chuyến dù chưa được gán explicitly (dùng default)

### Goal 4 — Enterprise Import

Pipeline 5 bước: Upload → Mapping → Preview/Validate → Auto-fix → Partial Import

### Goal 5 — Tables song song, migration an toàn

Toàn bộ tables mới dùng prefix `tp_`. Hệ thống cũ (`policy_*`) vẫn chạy song song cho đến khi cutover xong.

---

## 4. Phạm vi

### Trong phạm vi (In scope)

- Thiết kế và tạo mới toàn bộ bảng `tp_*`
- Module quản lý Chương trình đưa đón (CRUD + lifecycle)
- Module quản lý Học sinh (CRUD + enterprise import)
- Tự động sinh lịch vận hành (sync, không cron)
- Đăng ký học sinh vào chương trình (bulk)
- Điều chỉnh vắng theo từng ngày (sparse model)
- Gán tài xế/xe per-program và per-day override
- Chi phí ước tính và thực tế per-day
- Driver mobile app: board/alight/absent + offline sync
- Dashboard điều vận realtime
- Báo cáo vắng (pivot table)
- Audit trail toàn bộ domain
- Migration strategy từ schema cũ

### Ngoài phạm vi (Out of scope)

- Tích hợp thanh toán / billing
- App phụ huynh (parent app)
- GPS tracking / live location
- Tuyến hỗn hợp (policy + non-policy trên cùng xe)
- Hệ thống thông báo SMS (push notification đã đủ)
- Refactor hệ thống trips thông thường (door-to-door)

---

## 5. Stakeholders

| Vai trò | Liên quan đến module |
|---------|---------------------|
| Admin trường | Tạo/quản lý chương trình, quản lý học sinh |
| Điều vận | Gán tài xế, theo dõi ngày vận hành, đánh dấu vắng |
| Tài xế | Mobile app: bắt đầu/hoàn thành chuyến, điểm danh |
| Kế toán | Xem chi phí thực tế, export báo cáo |

---

## 6. Ràng buộc kỹ thuật

- **Backend:** Laravel (PHP), PostgreSQL
- **Frontend:** Vue 3 + Inertia hoặc SPA
- **Mobile:** App driver hiện tại (Vue/React Native — cần confirm stack)
- **Offline:** Driver app phải hoạt động khi mất mạng, sync khi có mạng
- **Realtime:** SSE cho dashboard điều vận (không dùng WebSocket)
- **Table prefix `tp_`:** Bắt buộc để tránh conflict với bảng cũ
