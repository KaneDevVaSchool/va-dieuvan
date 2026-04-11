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

Nguồn yêu cầu (trường **kênh** trên form, để phân loại nguồn tin):
- **Tạo trực tiếp trên hệ thống** (portal)
- **Ghi nhận nguồn Zalo** — Dispatcher có thể tạo yêu cầu và chọn kênh `zalo` khi nhập thay; **chưa có** tích hợp bot/OA Zalo tự động đẩy yêu cầu vào hệ thống.
- **Phiếu giấy đề xuất** (`paper`): lưu online, upload scan, đánh dấu đã nhận phiếu (theo quyền)

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
- **Phiếu đề xuất / scan**: trên web có upload kèm tiến trình; danh sách scan trên chi tiết yêu cầu; có thể **đánh dấu đã nhận phiếu giấy** (cập nhật trạng thái phiếu, mã tham chiếu, thời điểm nhận — chỉ user được cấp quyền quản lý phiếu giấy).
- **POD hàng hóa**: upload POD theo từng shipment; trên danh sách cargo **hiển thị các POD đã có** (link + xem nhanh ảnh nếu là file ảnh).
- Hóa đơn/biên lai chi phí (gắn Trip/cost qua API attachment — tùy quyền)

### G) Báo cáo tổng quan (bản cơ bản)

**API** trả về trong khoảng thời gian chọn:
- Số chuyến theo trạng thái
- Tổng chi phí **đã confirmed** theo loại (xăng/cầu đường/…)
- Tổng chi phí theo **NCC / nội bộ** (từng nhà cung cấp hoặc nội bộ)
- Số shipment hàng hóa **đang trễ SLA** (chưa giao/chưa hủy nhưng đã quá hạn SLA)

**Giao diện web** (Tổng quan + trang Báo cáo) hiện hiển thị chủ yếu **ba khối**: trips theo trạng thái, chi phí theo loại, và số lần vi phạm SLA cargo. **Chưa có bảng/chart “chi phí theo NCC” trên màn hình** — dữ liệu đã có ở backend, có thể bổ sung UI sau.

### H) Phân quyền & nhật ký hệ thống

- Người dùng chỉ thao tác theo vai trò/quyền được cấp
- Có **audit log** ghi nhận ai làm gì, lúc nào (các thao tác chính)
- Một số thao tác ghi nhận trùng lặp được giảm nhờ **Idempotency-Key** (gửi lại cùng một thao tác không tạo bản ghi trùng) — chi tiết kỹ thuật không bắt buộc với BA.

### I) Giao diện web (Vue) — đối chiếu với bản triển khai hiện tại

Đã có **ứng dụng web** (đăng nhập, menu, responsive) với các trang chính:

| Trang (đường dẫn) | Việc làm được trên UI (mức vận hành) |
| --- | --- |
| Tổng quan `/` | Xem nhanh 3 chỉ số báo cáo; link thao tác nhanh |
| Yêu cầu `/requests`, chi tiết `/requests/:id` | Lọc (trạng thái, loại, kênh, **trạng thái phiếu giấy**); tạo mới qua `/dispatch-requests/new`; duyệt/từ chối; upload scan; đánh dấu đã nhận phiếu |
| Chuyến `/trips`, `/trips/:id` | Lọc danh sách; xem chi tiết; **gán** xe/tài xế/NCC (nhập ID); đổi trạng thái chuyến |
| Chi phí `/costs` | Danh sách chi phí; form nhập chi phí nhanh (cần **Trip ID**) |
| Đối soát & thanh toán `/payments` | Tạo kỳ, khóa kỳ, tạo payment theo Trip ID, thực hiện thanh toán (form cơ bản) |
| Hàng hóa `/cargo` | Tạo shipment; lọc; **xem POD đã có + upload POD mới** |
| Tuyến D2D `/routes`, Học sinh `/students` | **Xem danh sách** (dạng tối giản); chưa có đủ wizard nghiệp vụ trên UI |
| Báo cáo `/reports` | Chọn từ–đến, tải cùng loại số liệu như tổng quan |
| Activity log `/audit-logs` | Xem nhật ký (theo API) |

**Ghi chú:** Nhiều màn hình vẫn dùng **nhập số ID** (xe, tài xế, trip…) thay vì chọn từ danh bạ — phù hợp giai đoạn nối API, chưa phải form nghiệp vụ “đóng gói” hoàn chỉnh.

---

## 4) Những tính năng còn thiếu (BA cần kiểm tra)

### A) Giao diện người dùng (UI)

**Đã có** nền tảng web và các trang cốt lõi (mục **3.I**). Phần còn **mỏng hoặc chưa đủ** so với vận hành “đóng hộp”:
- Form chọn **xe / tài xế / NCC** từ danh sách (thay vì gõ ID); kiểm tra lịch trực quan
- **Lịch** xe/tài xế (calendar) và dashboard theo thời gian thực
- Màn đối soát dạng **biên bản / chứng từ** (in/xuất) trực tiếp trên UI
- Door-to-door & học sinh: UI chủ yếu **đọc danh sách** — thiếu quy trình đầy đủ (tạo tuyến, duyệt version, gán HS…) trên màn hình
- Cargo: có danh sách + POD; thiếu **máy trạng thái** (workflow) và cảnh báo chủ động trên UI
- Báo cáo: thiếu hiển thị **chi phí theo NCC** và báo cáo chi tiết/xuất file trên giao diện (xem thêm **4.E**)

### B) Door-to-door “đúng nghiệp vụ”

Đã có khung tuyến + version + lịch + học sinh + sinh Trip theo ngày, nhưng còn thiếu:
- Import danh sách học sinh từ Excel/nguồn tuyển sinh
- Điểm danh theo tuyến (đưa/đón)
- Sinh Trip hàng loạt theo lịch (tuần/tháng) + tự động cập nhật khi đổi version
- Quy trình phối hợp với NCC (duyệt tuyến, khảo sát, phản hồi)

### C) Hàng hóa (Cargo) “đủ SLA 3 giờ”

Đã có SLA mặc định khi tạo shipment, đếm vi phạm SLA trên báo cáo, upload/list POD trên web; còn thiếu:
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

