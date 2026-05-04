# Tiêu thụ API (HTTP) và token (AI / Cursor)

Hai loại “tiết kiệm”: **request tới server** và **ngữ cảnh khi chat với AI**.

## 1. Giảm số lần gọi API (backend)

### Nguyên tắc

- **Một màn hình, một lần “tải khung”**: gom dữ liệu cần thiết cho view vào ít request nhất có thể; tránh `mounted()` gọi 5–10 API nhỏ nếu backend đã có (hoặc nên có) endpoint tổng hợp.
- **Đọc vs ghi**: các route trong `common-read` / `*-read` được thiết kế nhẹ và không log từng request; ưu tiên dùng cho list/detail chỉ đọc. Thao tác tạo/sửa qua file `*-mutate` — **không** chuyển mutate sang read chỉ để “tránh log”.
- **Tránh polling**: không `setInterval` gọi API liên tục; nếu cần realtime, dùng một kênh duy nhất (SSE/WebSocket/push) đã có trong project — xem `resources/js/src/core/integrations/realtime.js` nếu áp dụng.
- **Cache phía client**: danh mục ít đổi (reference) có thể cache trong Pinia hoặc memory với thời hạn; không refetch mỗi lần chuyển tab trong cùng phiên.
- **Phân trang / lọc**: truyền query chuẩn một lần thay vì tải full list rồi lọc trên trình duyệt với dataset lớn.
- **Upload / form**: submit một lần; dùng `idempotency` middleware nếu endpoint hỗ trợ (tránh double-submit tạo bản ghi trùng).

### Khi thêm API mới

- Cân nhắc trả về đủ field cho màn hình (Resource JSON rõ ràng) thay vì bắt frontend gọi thêm nhiều lần “chi tiết phụ”.
- Giữ response gọn: không select `*` quan hệ nặng nếu không hiển thị.

## 2. Giảm token và ngữ cảnh (Cursor / LLM)

Mục tiêu: trả lời đúng việc với **ít file, ít dòng** đính kèm.

### Trong chat

- **Chỉ đính (@)** file liên quan trực tiếp: ví dụ một route spa + một controller + một view; không `@folder` toàn `resources/js` nếu không cần.
- **Mô tả việc trong 3–6 câu**: màn hình nào, hành động nào, lỗi hoặc hành vi mong muốn; tránh dán log dài — chỉ phần lỗi chính.
- **Không** dán cả SRS: dùng `docs/TIEP_TUC_PHAT_TRIEN.md` + đoạn FR/UC cụ thể.
- **`/summarize` hoặc tái sử dụng** kết quả đã rõ trong thread thay vì nhắc lại toàn bộ code.

### Trong repo

- Ưu tiên cập nhật **một** tài liệu định hướng (`docs/`) thay vì nhiều file markdown trùng ý.
- PR/commit nhỏ: diff nhỏ = AI đọc ít hơn khi review.

### `.cursorrules`

- Quy ước dài đã có trong `.cursorrules`; không nhân bản sang MD khác — trong `docs/README.md` chỉ link tới đó.

## 3. Sanctum / phiên đăng nhập

- Mỗi thiết bị một token; **không** tạo thêm luồng login chỉ để “refresh” nếu chưa cần — logout/login có chủ đích khi đổi quyền.
- Tránh lưu nhiều bản copy token ở nhiều key `localStorage` không đồng bộ; dùng một `TOKEN_KEY` chung.

---

Tóm lại: **ít request hơn, mỗi request có ích hơn**; với AI **ít file @ hơn, prompt sát việc hơn**.
