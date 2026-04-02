<!-- markdownlint-disable MD022 MD032 MD012 -->

# TỔNG QUAN TÍNH NĂNG (Phi kỹ thuật) – Phần mềm điều vận

Tài liệu này dành cho người không chuyên kỹ thuật (BA/Operations/Quản lý) để hiểu:
- Hệ thống **đang có những tính năng nào**
- Hệ thống **đang thiếu những gì** so với nhu cầu vận hành
- Những phần nào **để triển khai ở giai đoạn sau**

---

## 1) Hệ thống dùng để làm gì?

Phần mềm điều vận giúp quản lý **toàn bộ vòng đời** một nhu cầu điều xe:

**Yêu cầu → Duyệt → Tạo chuyến (Trip) → Phân công xe/tài xế/NCC → Theo dõi thực hiện → Ghi nhận chi phí → Đối soát → Thanh toán → Báo cáo**

Điểm quan trọng: **Trip là “trục dữ liệu trung tâm”**, mọi thứ (nhật ký, chi phí, chứng từ, thanh toán) đều gắn vào Trip để truy vết.

---

## 2) Ai dùng hệ thống?

- **User nội bộ (Người đề xuất)**: tạo yêu cầu điều xe, theo dõi trạng thái.
- **Dispatcher (Điều phối viên)**: nhận yêu cầu, duyệt/từ chối, phân công xe/tài xế/NCC, theo dõi chuyến.
- **Tài xế**: cập nhật trạng thái chuyến, ghi nhận sự kiện, nhập nhật ký/chi phí phát sinh.
- **Kế toán**: xác nhận chi phí, tạo kỳ đối soát, thực hiện thanh toán.
- **Admin**: quản trị, phân quyền, xem audit log, override khi cần.

---

## 3) Hiện tại đã có những tính năng nào?

### A) Tạo yêu cầu (Request)

Hệ thống đã hỗ trợ tạo yêu cầu với các loại:
- Door-to-door
- Point-to-point
- Công tác
- Hàng hóa

Nguồn yêu cầu:
- **Tạo trực tiếp trên hệ thống**
- **Nhận từ Zalo** (Dispatcher có thể tạo thay người đề xuất)
- **Phiếu giấy đề xuất** (nhận phiếu sau, lưu online)

Quy tắc:
- **Yêu cầu nên tạo trước ≥ 2 tiếng**
- Nếu là **lệnh gấp** thì có thể điều xe trước, nhận phiếu sau

### B) Duyệt/Từ chối yêu cầu

Dispatcher/Admin có thể:
- Duyệt yêu cầu → hệ thống sinh **Trip**
- Từ chối yêu cầu → lưu lý do

### C) Tạo & điều phối chuyến (Trip)

Dispatcher có thể phân công:
- Xe nội bộ + Tài xế
- Hoặc NCC/Taxi (dịch vụ ngoài)

Chống trùng lịch:
- Hệ thống kiểm tra **trùng lịch xe/tài xế** và chặn phân công nếu trùng.

### D) Theo dõi vận hành chuyến

Có thể cập nhật:
- Tài xế xác nhận / bắt đầu / hoàn thành / báo sự cố / hủy
- Ghi lại nhật ký sự kiện theo chuyến (event log)

### E) Chi phí – đối soát – thanh toán

Chi phí gắn với Trip:
- Tài xế/điều phối submit chi phí (xăng/cầu đường/khác…)
- Kế toán confirm/reject chi phí
- Admin có quyền override (khi thật sự cần)

Đối soát theo kỳ:
- Kế toán tạo kỳ đối soát (tuần/tháng), lock kỳ
- Tạo danh sách thanh toán cho các Trip
- Thực hiện thanh toán và đánh dấu Trip đã thanh toán

### F) Lưu trữ file (chứng từ/POD/phiếu đề xuất)

Hệ thống đã hỗ trợ upload file để lưu online, ví dụ:
- Scan/PDF phiếu đề xuất
- Ảnh POD (Proof of delivery) cho hàng hóa
- Hóa đơn/biên lai chi phí

### G) Báo cáo tổng quan (bản cơ bản)

Có báo cáo tổng hợp:
- Số chuyến theo trạng thái
- Tổng chi phí theo loại (xăng/cầu đường/…)
- Tổng chi phí theo NCC/nội bộ
- Số đơn hàng hóa đang vi phạm SLA

### H) Phân quyền & nhật ký hệ thống

- Người dùng chỉ thao tác theo vai trò/quyền được cấp
- Có **audit log** ghi nhận ai làm gì, lúc nào (các thao tác chính)

---

## 4) Những tính năng còn thiếu (BA cần kiểm tra)

### A) Giao diện người dùng (UI)

Hiện tại phần lớn là **API + nền dữ liệu**, chưa có đầy đủ màn hình nghiệp vụ cho:
- Nhập/duyệt yêu cầu theo form chuẩn
- Lịch xe/tài xế (calendar)
- Màn hình theo dõi Trip theo thời gian thực (dashboard)
- Màn hình đối soát/biên bản thanh toán
- Màn hình quản lý tuyến door-to-door, quản lý học sinh
- Màn hình quản lý hàng hóa & POD

### B) Door-to-door “đúng nghiệp vụ”

Đã có khung tuyến + version + lịch + học sinh + sinh Trip theo ngày, nhưng còn thiếu:
- Import danh sách học sinh từ Excel/nguồn tuyển sinh
- Điểm danh theo tuyến (đưa/đón)
- Sinh Trip hàng loạt theo lịch (tuần/tháng) + tự động cập nhật khi đổi version
- Quy trình phối hợp với NCC (duyệt tuyến, khảo sát, phản hồi)

### C) Hàng hóa (Cargo) “đủ SLA 3 giờ”

Đã có SLA + POD + cảnh báo nội bộ, còn thiếu:
- Luồng nghiệp vụ chi tiết theo từng trạng thái (đang xử lý/đã giao/hoàn trả…)
- Tự sinh mã tracking chuẩn
- Thông báo đa kênh (email/Zalo OA/SMS) nếu cần
- Báo cáo SLA theo thời gian/đơn vị

### D) Công tác (Business trip) – hạn mức theo người

BRD có nhu cầu:
- Gắn chi phí theo từng cá nhân
- Hạn mức/quota theo tháng, cảnh báo vượt mức

Phần này **chưa triển khai**.

### E) Báo cáo & xuất file (Excel/PDF)

Đã có báo cáo tổng quan cơ bản, còn thiếu:
- Report chi tiết theo mẫu vận hành/kế toán
- Xuất Excel/PDF theo form chuẩn

### F) Chính sách “khóa sửa” sau khi xác nhận/đã thanh toán

BRD thường yêu cầu:
- Sau khi kế toán confirm chi phí hoặc sau khi thanh toán, dữ liệu bị khóa sửa (trừ Admin override)

Hiện mới có **Admin override**, nhưng quy tắc khóa sửa chi tiết cần BA xác nhận và triển khai chặt hơn.

### G) Bảo mật dữ liệu nhạy cảm

Một số thông tin như CCCD tài xế… có thể cần mã hoá/ẩn theo quy định nội bộ.  
Chưa triển khai đầy đủ theo chính sách bảo mật.

---

## 5) Tính năng để giai đoạn sau (đã dự phòng, chưa làm)

- **Ký số online** cho phiếu đề xuất (tích hợp eSign)
- **GPS tracking realtime**
- **AI gợi ý điều phối** (tối ưu xe/tài xế)
- Ứng dụng mobile native (nếu có)

---

## 6) Câu hỏi BA nên chốt để tránh thiếu scope

1) “Lệnh gấp” cần quy trình duyệt riêng hay chỉ đánh dấu `urgent`?
2) Phiếu đề xuất có cần bắt buộc upload trước khi duyệt không?
3) Trạng thái Trip cần thêm gì? (ví dụ “Đã thanh toán” là trạng thái riêng hay chỉ là cột thanh toán)
4) Cargo SLA: cảnh báo cho ai? kênh nào? có mức độ ưu tiên không?
5) Door-to-door: import data từ đâu? ai chịu trách nhiệm cập nhật tuyến/học sinh? có approval workflow với NCC không?
6) Báo cáo/biên bản: mẫu Excel/PDF cụ thể (đính kèm form) để dev implement đúng.

