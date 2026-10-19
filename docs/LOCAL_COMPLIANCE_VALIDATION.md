# Kết quả kiểm tra local — 19/10/2026

Đã chạy trên MySQL thật mà `.env` local đang kết nối: `127.0.0.1:3306`, database `va_dieuvan_db_staging` (ServBay). Không tạo backup theo yêu cầu người dùng.

Trước khi kiểm tra, database có **0 attachment chứng từ xe/tài xế**, nên không có dữ liệu nghiệp vụ cũ để chuyển. Đã chạy dry-run/apply/verify/prune trên database này, sau đó tạo riêng dữ liệu tạm để kiểm chứng thực tế.

## Chuyển dữ liệu và file

- Tạo hai chứng từ cũ chỉ có binary trong MySQL: bảo hiểm xe và GPLX tài xế.
- Tải binary cũ qua API và đối chiếu SHA-256.
- Chạy `compliance:migrate-files --apply`, `--verify`, `--apply --prune-binary`, `--verify`.
- Cả hai file được ghi vào disk `compliance`, giữ đúng dung lượng/SHA-256, binary chuyển thành NULL.
- Sau các thao tác upload/gia hạn, xác minh cả **5 attachment** gồm bản mới và lịch sử: file tồn tại, dung lượng/hash đúng, không attachment nào chứa binary.

Kho private local đang dùng: `D:/va-dieu-van/storage/app` theo cấu hình mặc định. Các file nằm trong hai nhánh `attachments/vehicle_compliance_documents` và `attachments/driver_compliance_documents`.

## Kiểm tra Chromium với API và database thật

15 kiểm tra đều đạt:

1. PDF cũ sau chuyển tải đúng SHA-256.
2. Tài khoản thiếu quyền và người chưa đăng nhập bị chặn tải.
3. Trang chi tiết xe tải được bằng tài khoản điều vận.
4. Tải PDF bằng nút trên trình duyệt.
5. Gia hạn thiếu file bị chặn trên giao diện.
6. Gia hạn bảo hiểm bằng PNG mới, giữ PDF cũ trong lịch sử.
7. Xem trước và tải PNG đúng nội dung.
8. Reload trang vẫn có lịch sử và tải được file cũ.
9. Tạo đăng kiểm mới bằng PDF qua form upload.
10. Gia hạn GPLX qua giao diện.
11. Kho chứng từ tìm đúng hồ sơ xe tạm và file hiện hành.
12. API chặn hạn gia hạn đã qua.
13. API chặn upload HTML.
14. Không có JavaScript runtime error trên các trang đã thử.
15. Popup xe trong danh sách xem/tải được PNG private.

Các ảnh kiểm chứng và kết quả JSON lưu tại `storage/app/compliance-local-validation/` (không đưa vào Git).

## Lỗi phát hiện và sửa trong lượt kiểm tra

Gia hạn bị 429 sau nhiều lượt đọc vì các middleware throttle số dùng chung bộ đếm. Đã tách tám route ghi chứng từ sang limiter `compliance-mutate` (30 thao tác/phút/người dùng), giữ giới hạn chung của API. Test hồi quy thực hiện 35 lượt đọc rồi gia hạn: trước sửa lỗi 429, sau sửa thành công.

## Kiểm tra cuối và dọn dữ liệu

- PHPUnit liên quan: **53 tests / 378 assertions**, tất cả đạt.
- Laravel Pint và `git diff --check`: đạt.
- Xóa tài khoản, token, xe/tài xế, chứng từ, attachment/file và audit do bộ test tạm tạo.
- Sau dọn: database trở về **0 attachment chứng từ, 0 binary**; lệnh verify báo `failed=0`.

Đây là xác nhận luồng chứng từ/lưu trữ trên local, không phải kiểm tra toàn bộ chức năng khác của ứng dụng. Chưa chạy chuyển dữ liệu trên production.
