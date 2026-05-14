# Kế hoạch triển khai — Sprint sau Demo 02
> Tài liệu này dành cho BA / Product Owner review trước khi dev bắt tay làm.
> Sau khi đọc, feedback vào phần **"Góc phản hồi"** bên dưới mỗi task.

---

## Tổng quan nhanh

| # | Tên task | Loại | Độ phức tạp | Ước tính |
|---|----------|------|-------------|----------|
| ~~1.1~~ | ~~Đổi nhãn "Trưởng đoàn phụ trách"~~ | ~~Sửa UI~~ | — | **Đã xong** |
| ~~1.2~~ | ~~Xóa trường "Dùng cho 3+ ngày"~~ | ~~Sửa UI~~ | — | **Đã xong** |
| **2.1** | Luồng duyệt 5 bước + khóa PDF | Workflow | Cao | 3–5 ngày |
| **3.1** | Phiếu định kỳ + cập nhật số học sinh | Tính năng mới | Cao | 4–6 ngày |
| **3.2** | Link Bảng giá tham chiếu | Cải tiến UX | Thấp | 0.5 ngày |
| **3.3** | Hồ sơ xe – giấy tờ xe | Tính năng mới | Trung bình | 2–3 ngày |
| 3.4 | P2P học sinh chính sách | Module mới | — | **Roadmap — chưa làm** |

---

## TASK 2.1 — Luồng duyệt 5 bước & khóa xuất PDF

### Vấn đề hiện tại
> Người đề xuất hiện tại có thể bấm **"Xuất PDF"** ngay sau khi tạo phiếu — lúc đó phiếu chưa có giá, chưa được duyệt → phiếu in ra thiếu thông tin, không đúng quy trình.

### Sẽ thay đổi như thế nào

#### Luồng 5 bước mới

```
Bước 1  →  Bước 2  →  Bước 3  →  Bước 4A / 4B  →  Bước 5
 Tạo phiếu   Fill giá   Trưởng ĐV    PDF mở khóa /    Upload
 (Người đề   (Điều vận)   duyệt      Điều phối xe      phiếu ký
  xuất)                              (song song)
```

#### Chi tiết từng thay đổi trên màn hình

---

**Màn hình: Chi tiết phiếu đề xuất** (`/requests/:id`)

| Ai nhìn | Khi nào | Thay đổi |
|---------|---------|----------|
| NV Điều vận | Phiếu đang ở trạng thái **"Chờ xử lý"** | Xuất hiện ô nhập **đơn giá dịch vụ** + nút **"Lưu giá & chuyển Trưởng đơn vị"** |
| Trưởng đơn vị | Phiếu ở trạng thái **"Chờ Trưởng đơn vị duyệt"** | Xuất hiện 2 nút: **[Duyệt]** và **[Từ chối]** — bắt buộc nhập lý do nếu từ chối |
| Tất cả | Phiếu chưa được duyệt | Nút **"Xuất PDF"** hiển thị nhưng **bị xám** + tooltip giải thích |
| Tất cả | Phiếu đã được **"Đã duyệt"** | Nút **"Xuất PDF"** sáng lên, bấm được |
| Người đề xuất | Phiếu **"Đã duyệt"** | Xuất hiện nút **"Đính kèm phiếu đã ký"** để upload scan/ảnh chụp |

---

**Màn hình: Tạo phiếu — bước xác nhận** (bước cuối của wizard tạo phiếu)

| Thay đổi |
|---------|
| Sau khi gửi phiếu thành công, nút **"Tải PDF"** vẫn hiện nhưng **bị xám + tooltip**: *"PDF sẽ mở khóa sau khi Trưởng đơn vị duyệt"* |

---

**Trạng thái phiếu — nhãn mới**

| Mã trạng thái | Nhãn hiển thị cũ | Nhãn mới |
|--------------|-----------------|---------|
| `pending` | Chờ xử lý | Chờ xử lý *(giữ nguyên)* |
| `price_filled` | *(chưa có)* | **Chờ Trưởng đơn vị duyệt** |
| `approved` | Đã duyệt | Đã duyệt *(giữ nguyên)* |
| `rejected` | Từ chối | Từ chối *(giữ nguyên)* |

---

**Thanh tiến trình (stepper) trên phiếu**

Hiện tại stepper có 4 bước. Sẽ bổ sung thành 5 bước:

```
[1] Tạo phiếu → [2] Điều vận fill giá → [3] Trưởng ĐV duyệt → [4] Điều phối → [5] Lưu trữ
```

---

### Acceptance Criteria (dùng để test)

- [ ] AC1 — Người đề xuất **KHÔNG** tải được PDF khi phiếu chưa được Trưởng đơn vị duyệt; nút hiện nhưng bị mờ
- [ ] AC2 — NV Điều vận nhập giá → phiếu tự chuyển sang **"Chờ Trưởng đơn vị duyệt"**
- [ ] AC3 — Trưởng đơn vị nhận được **thông báo push** khi có phiếu chờ duyệt của phòng mình
- [ ] AC4 — Trưởng đơn vị **bắt buộc nhập lý do** khi từ chối; không thể bấm xác nhận nếu bỏ trống
- [ ] AC5 — Sau khi Trưởng đơn vị duyệt → nút PDF của Người đề xuất **sáng lên ngay**
- [ ] AC6 — Điều vận có thể điều phối xe **ngay sau khi duyệt**, không cần chờ Người đề xuất in PDF
- [ ] AC7 — Người đề xuất upload được file scan (PDF/JPG/PNG ≤ 10MB), file lưu vào lịch sử phiếu

### ❓ Góc phản hồi Task 2.1
> *(BA/PO ghi feedback tại đây trước khi dev làm)*
>
> - [ ] Luồng 5 bước này có đúng với thực tế vận hành chưa?
> - [ ] Trưởng đơn vị có được phép từ chối mà không nhập lý do không?
> - [ ] Sau khi Từ chối, Người đề xuất có được sửa lại và gửi lại không? *(hiện tại: có)*
> - [ ] Upload phiếu ký ở Bước 5 có bắt buộc không?

---

## TASK 3.1 — Phiếu định kỳ (Recurring) + Cập nhật số học sinh

### Vấn đề hiện tại
> CLB ngoại khóa (bơi lội, v.v.) có lịch cố định hàng tuần nhưng người đề xuất phải tạo phiếu **thủ công mỗi tuần** → tốn thời gian, dễ sai sót.

### Sẽ thay đổi như thế nào

---

**Màn hình: Tạo phiếu — loại P2P Ngoại khóa**

| Thay đổi |
|---------|
| Xuất hiện toggle **"Đề xuất định kỳ"** (mặc định tắt) |
| Khi bật toggle → hiện thêm phần cấu hình: **Ngày trong tuần** (chọn nhiều), **Giờ đi**, **Giờ về**, **Ngày bắt đầu**, **Ngày kết thúc** |
| Sau khi gửi → hệ thống **tự tạo chuỗi phiếu** theo lịch, không cần tạo lại mỗi tuần |

> **Lưu ý:** Mỗi phiếu trong chuỗi vẫn đi qua đầy đủ luồng duyệt 5 bước (Task 2.1).

---

**Màn hình: Chi tiết phiếu đề xuất** (phiếu thuộc chuỗi định kỳ)

| Ai | Khi nào | Thay đổi |
|----|---------|---------|
| Người đề xuất | Còn **> 24 giờ** trước giờ khởi hành | Có thể **chỉnh sửa số học sinh** trực tiếp trên phiếu |
| Người đề xuất | Còn **≤ 24 giờ** trước giờ khởi hành | Ô số học sinh **bị khóa** + thông báo *"Đã qua thời hạn cập nhật — liên hệ Điều vận nếu cần thay đổi khẩn"* |
| NV Điều vận | Bất kỳ lúc nào | Vẫn **chỉnh sửa được** số học sinh (xử lý trường hợp khẩn) |

---

**Màn hình: Chi tiết phiếu** — nút mới **"Đặt lại"** (Clone phiếu)

| Khi nào hiện | Tác dụng |
|-------------|---------|
| Phiếu đã **Đã duyệt** hoặc **Từ chối** | Tạo phiếu mới (draft) với toàn bộ thông tin được sao chép — người dùng chỉ cần sửa ngày và số học sinh |
| — | Phiếu mới vẫn phải qua đủ 5 bước duyệt |

---

**Cảnh báo vượt ngưỡng chi phí gói**

Khi tổng chi phí của chuỗi phiếu vượt ngưỡng đã đặt:
- Hiện **cảnh báo màu vàng** trên màn hình chi tiết phiếu cho cả Người đề xuất lẫn NV Điều vận
- Nội dung: *"Chi phí gói [tên gói] tháng [X] đã vượt ngưỡng [Y VND]…"*
- Cảnh báo **không chặn** tạo/duyệt phiếu — chỉ thông tin

---

**Màn hình: Danh sách phiếu** (`/requests`)

| Thay đổi |
|---------|
| Phiếu thuộc chuỗi định kỳ có badge **"Định kỳ"** nhỏ bên cạnh tiêu đề |
| Thêm tùy chọn lọc **"Chỉ phiếu định kỳ"** trong bộ lọc |

---

### Acceptance Criteria (dùng để test)

- [ ] AC1 — Tạo phiếu P2P Ngoại khóa với recurring → hệ thống tự sinh đúng số phiếu theo lịch
- [ ] AC2 — Người đề xuất sửa được số học sinh khi còn > 24h; bị chặn khi ≤ 24h
- [ ] AC3 — Điều vận vẫn sửa được số học sinh bất kỳ lúc nào
- [ ] AC4 — Cảnh báo vượt ngưỡng hiện đúng đối tượng
- [ ] AC5 — Nút "Đặt lại" tạo phiếu mới với dữ liệu prefill đầy đủ

### ❓ Góc phản hồi Task 3.1
> *(BA/PO ghi feedback tại đây trước khi dev làm)*
>
> - [ ] Ngoài P2P Ngoại khóa, loại phiếu nào khác có thể dùng recurring?
> - [ ] Sau khi chuỗi kết thúc (hết ngày), hệ thống có cần thông báo không?
> - [ ] "Gói dịch vụ" và ngưỡng chi phí — ai cấu hình? (Admin hay Người đề xuất?)
> - [ ] Nút "Đặt lại" — nên hiện ở những phiếu nào? (chỉ approved/rejected hay tất cả?)

---

## TASK 3.2 — Link Bảng giá tham chiếu

### Vấn đề hiện tại
> NV Điều vận phải mở tab riêng để tra bảng giá khi fill chi phí → gây gián đoạn workflow.

### Sẽ thay đổi như thế nào

---

**Màn hình: Chi tiết phiếu — ô nhập đơn giá** (chỉ NV Điều vận thấy)

| Thay đổi |
|---------|
| Cạnh ô nhập đơn giá, xuất hiện link **"Xem Bảng giá tham chiếu 🔗"** |
| Click → mở bảng giá trong **tab mới**, không rời trang hiện tại |
| **Nếu Admin chưa cài URL** → link không hiển thị (không lỗi) |

---

**Màn hình: Cài đặt hệ thống** (`/system/dispatch-settings`) — dành cho Admin

| Thay đổi |
|---------|
| Thêm trường nhập **"URL Bảng giá tham chiếu"** |
| Admin dán link Google Sheet / Drive / bất kỳ URL nào |
| Sau khi lưu → link xuất hiện ngay trên màn hình Điều vận mà **không cần deploy lại** |

---

### Acceptance Criteria (dùng để test)

- [ ] AC1 — Link hiện rõ cạnh ô nhập đơn giá trên màn hình NV Điều vận
- [ ] AC2 — Click link → mở đúng URL đã cài trong tab mới
- [ ] AC3 — Admin thay URL trong Settings → link trên màn hình Điều vận đổi theo ngay
- [ ] AC4 — Chưa cài URL → không có link, không có lỗi

### ❓ Góc phản hồi Task 3.2
> *(BA/PO ghi feedback tại đây trước khi dev làm)*
>
> - [ ] Link bảng giá có nên hiển thị ở bước xác nhận trong wizard tạo phiếu không?
> - [ ] Có cần phân quyền riêng để ẩn link với Người đề xuất không?

---

## TASK 3.3 — Hồ sơ xe & giấy tờ xe

### Vấn đề hiện tại
> Tài xế đã có màn hình hồ sơ với tab **"Giấy tờ"** (đăng ký lái xe, hộ chiếu…). Xe chưa có màn hình tương tự — không có nơi quản lý đăng kiểm, bảo hiểm xe.

### Sẽ thay đổi như thế nào

---

**Màn hình mới: Chi tiết xe** (`/resources/vehicles/:id`)

Tab **"Hồ sơ"**:

| Thông tin hiển thị |
|-------------------|
| Biển số xe |
| Loại xe / số chỗ |
| Nhà cung cấp (NCC) |
| Trạng thái hoạt động |
| Nút chỉnh sửa (nếu có quyền) |

Tab **"Giấy tờ"**:

| Thông tin & hành động |
|----------------------|
| Danh sách giấy tờ (đăng kiểm, bảo hiểm, …) với ngày hết hạn |
| Badge trạng thái: **Còn hạn** / **Sắp hết hạn** / **Đã hết hạn** |
| Nút **"Thêm giấy tờ"** → form nhập loại giấy, ngày hết hạn, upload file |
| Xem / xóa giấy tờ đã có |

---

**Màn hình: Danh sách nguồn lực** (`/resources/list` — tab xe)

| Thay đổi |
|---------|
| Tên xe trong danh sách trở thành **link** → click vào dẫn đến trang Chi tiết xe |

---

### Acceptance Criteria (dùng để test)

- [ ] AC1 — Bấm vào xe trong danh sách → mở được màn hình Chi tiết xe
- [ ] AC2 — Thêm giấy tờ cho xe (đăng kiểm) → xuất hiện trong danh sách tab Giấy tờ
- [ ] AC3 — Giấy tờ hết hạn hiện badge đỏ; còn hạn hiện badge xanh
- [ ] AC4 — Xóa giấy tờ → xác nhận trước khi xóa

### ❓ Góc phản hồi Task 3.3
> *(BA/PO ghi feedback tại đây trước khi dev làm)*
>
> - [ ] Những loại giấy tờ nào cần theo dõi cho xe? (đăng kiểm, bảo hiểm, phù hiệu…)
> - [ ] NCC (Nhà cung cấp) có cần trang chi tiết tương tự không? (sprint này chưa có)
> - [ ] Cần cảnh báo/thông báo khi giấy tờ sắp hết hạn không? (ví dụ: 30 ngày trước)

---

## TASK 3.4 — P2P Học sinh chính sách *(Roadmap — chưa triển khai)*

> Task này cần grooming thêm. Dev sẽ **không làm trong sprint này**.
> Xem brief chi tiết trong tài liệu SRS v1.3.

---

## Những gì KHÔNG thay đổi trong sprint này

- Luồng tạo phiếu cơ bản (wizard 4 bước hiện tại) — **giữ nguyên**
- Màn hình tài xế — **giữ nguyên**
- Module hàng hóa (Cargo) — **giữ nguyên**
- Tuyến D2D — **giữ nguyên**
- Phân quyền & vai trò hiện có — **giữ nguyên** (chỉ bổ sung `request.approve_dept` cho Trưởng đơn vị)

---

## Câu hỏi tổng quát cần xác nhận trước khi dev bắt đầu

1. **Trưởng đơn vị** có role gì trong hệ thống hiện tại? → Xác nhận là `department_head`
2. Khi Trưởng đơn vị **Từ chối**, Người đề xuất có nhận **thông báo push** không?
3. Phiếu loại **Xe Công Tác** và **Hàng Hóa** có đi qua luồng 5 bước không, hay chỉ P2P Ngoại khóa?
4. Số lượng **loại giấy tờ xe** cần quản lý — có danh sách cố định hay admin tự thêm loại mới?

---

*Cập nhật lần cuối: 14/05/2026 — Dev Brief Sprint Post Demo 02*
