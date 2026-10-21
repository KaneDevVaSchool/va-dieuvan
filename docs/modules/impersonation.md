# Giả lập người dùng

Luồng tham chiếu: tính năng giả lập của SIS trong VA AGN.

Đăng nhập bằng **Super Admin**, mở **Hệ thống → Phân vai trò nhân viên**, chọn **Giả lập** trên thẻ người dùng. Chọn một vai trò người đó đang có rồi xác nhận. Hệ thống chuyển đến phân hệ tương ứng, dùng đúng danh tính và dữ liệu của người được chọn. Banner màu cam cho biết người/vai trò đang trải nghiệm; **Thoát giả lập** khôi phục phiên Super Admin và quay lại trang phân vai trò.

- Chỉ Super Admin được bắt đầu; không giả lập chính mình, Super Admin khác hoặc tài khoản bị khóa. Không cho phép giả lập lồng nhau.
- **Portal người yêu cầu**: luôn có trong danh sách chọn. User chưa có role mặc định chọn Portal; user có role cũng có thể chọn Portal để xem `/portal` như người yêu cầu. Phiên Portal bỏ toàn bộ role (token mang ability `as_portal`), chỉ giữ quyền gán trực tiếp, nên không gọi được API staff/tài xế. API: `POST /api/impersonate/{user}` với `{ "portal": true }`.
- User nhiều role phải chọn một role. Quyền hiệu lực gồm quyền role đã chọn và quyền gán trực tiếp cho user, giống SIS. Không thay đổi role trong DB.
- Phiên Sanctum riêng hết hạn sau 2 giờ. Token gốc vẫn được giữ; thoát thu hồi token giả lập. Token hết hạn hoặc quyền giả lập bị thu hồi thì giao diện khôi phục tài khoản gốc. Đăng xuất thu hồi cả hai phiên.
- `impersonation.started`, `impersonation.ended` và log API ghi nhận người giả lập. Thao tác trong phiên giả lập vẫn ghi dữ liệu thật của user được chọn.
- Không đăng ký thiết bị Web Push hoặc xếp thao tác vào hàng đợi offline khi giả lập. Chuyển phiên xóa cache dashboard tài xế và tải lại giao diện.
- Giả lập tài xế không tự tạo hồ sơ tài xế hoặc cấp thêm role. Nếu hồ sơ chưa liên kết `drivers.user_id`, lịch chuyến vẫn trống — đúng tình trạng của tài khoản đó.

Không có migration mới. Deploy PHP và frontend cùng phiên bản, build lại assets; nếu server cache route thì tạo lại route cache theo quy trình hiện có.

Kiểm tra local, không chạy PHPUnit trên production:

```powershell
php vendor/bin/phpunit tests/Feature/ImpersonationTest.php
npm run build
npm run test:impersonation
```

PHPUnit dùng SQLite in-memory. Kiểm tra trình duyệt chạy SPA thật với API giả lập, không sửa DB vận hành hay gửi thông báo.
