# Triển khai bản sửa checklist và hồ sơ chi phí

## Thay đổi

- Tách giới hạn request đọc, ghi, gửi, upload và export cho cả tài xế lẫn back-office. Việc đọc/tự lưu không dùng chung bộ đếm gửi phiếu.
- Lỗi tự lưu checklist giữ các chỉnh sửa chưa lưu, chặn gửi khi chưa lưu được; không tự tải lại và xóa dữ liệu đang nhập khi gặp xung đột 409.
- Chặn chi phí trùng trong hồ sơ; kiểm tra lại lúc nộp, đồng bộ xe và thông tin hóa đơn theo chi phí gốc.
- Kiểm tra tên người bán/ngày mua cho các dòng không hóa đơn trước khi tự sinh BK.
- Hủy/trả lại TT/HU xử lý đồng thời BK con. Chặn hủy khi con đã ký/thanh toán; BK đính kèm không được thanh toán độc lập. Nộp lại cập nhật BK cũ.
- Phiếu Daily chỉ tham chiếu Weekly cùng tài xế, cùng xe, trong khoảng lịch sử hợp lệ.
- Nhắc mỗi phút, bắt kịp slot trong ngày; báo quá hạn qua nửa đêm/đầu tuần theo kỳ gốc, có chống trùng. Tắt module thì ngừng phát nhắc mới.
- Accountant vào được SPA theo quyền hiện có, mặc định mở hồ sơ chi phí khi đăng nhập.
- File mới của hai phân hệ dùng cùng kho private của compliance, DB không ghi binary mới. Có lệnh chuyển ảnh/scan cũ riêng.

Không có thay đổi schema DB trong bản sửa này. Không tự sửa hoặc xóa các hồ sơ tài chính đã tồn tại từ trước; dữ liệu cũ có sai lệch cần được rà soát theo nghiệp vụ. File Excel/PDF giữ mẫu xuất hiện có; bản in thực tế vẫn cần nghiệm thu.

## Trên server hiện tại

Đường dẫn dự án: `/home/dieuvan.vaschools.edu.vn/public_html/va-dieuvan`.
PHP: `/usr/local/lsws/lsphp81/bin/php`. User chạy ứng dụng: `dieuv2595`; tài khoản cập nhật source: `quangtm`.

1. Cập nhật source bằng quy trình deploy hiện tại của `quangtm`. Build frontend bằng `npm ci` và `npm run build` trong môi trường build/deploy; PHP cần giữ nguyên `APP_KEY` và cấu hình DB đang dùng.
2. Kho private đang dùng cho compliance tiếp tục dùng chung. Trong `.env`, giữ:

```dotenv
COMPLIANCE_STORAGE_ROOT=/home/dieuvan.vaschools.edu.vn/va-dieu-van-private
```

Nếu thư mục chưa tồn tại, chạy bằng root:

```bash
sudo install -d -o dieuv2595 -g dieuv2595 -m 0700 /home/dieuvan.vaschools.edu.vn/va-dieu-van-private
```

Thư mục cần nằm trong phạm vi `open_basedir` của PHP. Upload là dữ liệu trên filesystem, không đưa vào Git.

3. Từ thư mục dự án, chạy các lệnh sau bằng root (PHP chạy dưới user ứng dụng):

```bash
cd /home/dieuvan.vaschools.edu.vn/public_html/va-dieuvan
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan down
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan config:clear
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan route:clear
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan driver-workflow:migrate-files
```

Nếu `failed=0`, chuyển và xác minh:

```bash
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan driver-workflow:migrate-files --apply
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan driver-workflow:migrate-files --verify
```

Chỉ khi cả hai lệnh thành công (`failed=0`), xóa binary trong DB rồi xác minh lần nữa:

```bash
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan driver-workflow:migrate-files --apply --prune-binary
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan driver-workflow:migrate-files --verify
```

Lệnh giữ file nguồn, có thể chạy lại. `checked=0` nghĩa là không có file thuộc hai phân hệ này để chuyển. Nếu có lỗi, giữ nguyên binary, xử lý lỗi được báo rồi chạy lại; không tiếp tục bước prune.

4. Nạp cấu hình và khởi động lại worker theo quy trình hiện có:

```bash
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan config:cache
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan queue:restart
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan up
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan schedule:list
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan queue:failed
```

Nếu OpenLiteSpeed bật OPcache không kiểm tra timestamp, cần reload PHP bằng quy trình quản trị server đang sử dụng. Trình duyệt/PWA cần nhận bundle mới; tải lại app trước khi nghiệm thu.

## Scheduler và queue

`schedule:list` phải hiển thị `checklist:remind-drivers` mỗi phút. Cần có cron/systemd/panel gọi scheduler mỗi phút. Kiểm tra cấu hình đang có, tránh tạo thêm cron trùng:

```bash
sudo crontab -u dieuv2595 -l
sudo crontab -u quangtm -l
sudo crontab -l
ps -eo user,args | grep '[a]rtisan queue:work'
```

Nếu chưa có scheduler, thêm dòng sau bằng `sudo crontab -u dieuv2595 -e`, giữ nguyên các dòng cũ:

```cron
* * * * * cd /home/dieuvan.vaschools.edu.vn/public_html/va-dieuvan && /usr/local/lsws/lsphp81/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Worker phải nghe queue tương ứng với `DISPATCH_NOTIFICATIONS_QUEUE_DEFAULT` (mặc định `default`). Worker chỉ nghe `log-import`, `handle-import-service` hoặc `health-check` sẽ không xử lý nhắc checklist. Dùng Supervisor/systemd/panel hiện có để duy trì worker cho app này. Ví dụ lệnh worker khi dùng các queue mặc định:

```bash
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan queue:work --queue=urgent-notifications,default --sleep=3 --tries=3 --timeout=90
```

Lệnh trên chạy foreground; cấu hình trong trình quản lý tiến trình để tự khởi động lại. Web Push còn cần cấu hình push hợp lệ, đăng ký thiết bị và quyền nhận thông báo. Không dùng `checklist:remind-drivers --at=...` để thử tùy tiện trên production vì lệnh có thể gửi thật tới các tài xế chưa làm kỳ đó.

## Test nhanh sau deploy

1. Tài xế tạo Daily/Weekly, đánh dấu các mục và nhập thông tin chung. Tải lại: dữ liệu còn. Cho một mục Critical NG, thử thiếu ghi chú/ảnh rồi bổ sung: validation và kết quả đúng.
2. Với tài khoản thử, mô phỏng PATCH tự lưu bị lỗi bằng DevTools hoặc môi trường mock. Nộp khi chưa lưu được phải bị chặn, nội dung nhập không mất. Bỏ lỗi, thử lại: phiếu giữ ghi chú mới nhất.
3. Tạo TT/HU có dòng không hóa đơn: thiếu tên người bán/ngày mua phải bị chặn; đủ thông tin mới sinh BK.
4. Gắn chi phí đã khai của xe B vào hồ sơ có xe mặc định A: dòng vẫn thuộc B. Cùng một chi phí chỉ được có một dòng. Tổng hồ sơ và sổ khớp.
5. Hủy TT có BK chưa ký: cả hai cancelled, chi phí mới chưa xác nhận được giải phóng. BK đã ký/thanh toán: chặn hủy. Trả lại rồi nộp lại: không sinh trùng BK/chi phí.
6. Đăng nhập tài khoản chỉ có role accountant: vào được hồ sơ/sổ chi phí; không có quyền checklist/quản trị thì API tương ứng trả 403.
7. Upload ảnh checklist/scan chi phí mới, xem và tải lại được. Người không có quyền không tải được. DB của file mới có `disk=compliance`, `path`, `sha256`, `file_binary IS NULL` (nếu dùng disk mặc định).
8. Cấu hình slot thử cho tài khoản thử, xác nhận nhắc trong app/thiết bị, không trùng, không nhắc khi đã hoàn thành kỳ. Kiểm tra slot qua nửa đêm trong môi trường kiểm thử trước khi đưa vào lịch thật.
9. Xuất PDF/XLSX với nhiều dòng và nội dung dài. Kiểm tra tổng tiền, đúng xe, ngắt trang và chữ ký trên A4. Daily không lấy Weekly của xe khác hay thời điểm tương lai.

## Kiểm thử local

```powershell
php vendor/bin/phpunit tests/Feature/Checklist tests/Feature/Expenses tests/Feature/DriverWorkflowRegressionTest.php tests/Feature/DriverWorkflowFileMigrationTest.php tests/Feature/ComplianceFileMigrationTest.php tests/Feature/ComplianceDocumentRenewalTest.php tests/Feature/DocumentCodeGeneratorTest.php tests/Unit/Support/VietnameseMoneyWordsTest.php tests/Unit/Support/ChecklistResultCalculatorTest.php tests/Unit/Admin/UserRoleServiceTest.php tests/Feature/Admin/BulkUserRolesUpdateTest.php
npm run build
npm run test:driver-checklist-autosave
```

PHPUnit dùng SQLite in-memory theo `phpunit.xml`; kiểm tra trình duyệt dùng API giả lập, không gửi thông báo thật và không ghi DB vận hành. Không chạy PHPUnit trên production.

Kết quả local ngày 19/10/2026: **105 test, 672 assertion đạt**; Pint đạt; Vite/PWA build thành công; Chromium xác nhận tự lưu lỗi không mất ghi chú và đăng nhập accountant mở đúng màn hình chi phí. Migration dry-run trên DB local đang cấu hình: `checked=0; ok=0; failed=0`, không có file cũ thuộc hai phân hệ cần chuyển. Chưa deploy hoặc gửi thông báo thật trên production.
