# Rà soát checklist tài xế và hồ sơ chi phí xe

Ngày kiểm tra: 19/10/2026. Các phát hiện dưới đây ghi lại trạng thái trước khi sửa. Phạm vi: API bằng SQLite in-memory, giao diện bằng Chromium với API giả lập, đối chiếu workbook mẫu tải trực tiếp từ Google Sheets. Chưa kiểm tra cron, queue hoặc thông báo trên thiết bị thật của production.

## Cập nhật sau sửa

Đã sửa F01–F09, đồng bộ file mới của checklist/chi phí sang disk private chỉ lưu metadata trong DB, bổ sung chuyển file cũ và cho role accountant truy cập SPA theo quyền hiện có. Bộ hồi quy thường trực: `DriverWorkflowRegressionTest.php`, `DriverWorkflowFileMigrationTest.php`; kiểm tra giao diện: `npm run test:driver-checklist-autosave`.

Lần chạy migration dry-run trên DB local đang cấu hình: `checked=0; ok=0; failed=0`; không có ảnh checklist/scan chi phí cũ cần chuyển. Các kết luận lỗi và giới hạn 5 phút ở phần lịch sử bên dưới không mô tả hành vi mã mới. Hướng dẫn triển khai và nghiệm thu: [DRIVER_WORKFLOW_DEPLOYMENT.md](DRIVER_WORKFLOW_DEPLOYMENT.md).

## Kết luận trước khi sửa

Hai phân hệ đã có phần lớn chức năng, nhưng chưa đủ điều kiện nghiệm thu toàn bộ. Có lỗi chặn gửi phiếu, mất dữ liệu tự lưu và sai tính nhất quán hồ sơ chi phí. Cần sửa các lỗi ưu tiên cao trước khi mở rộng sử dụng.

## Đối chiếu yêu cầu và mẫu

Nguồn: [Workbook người dùng cung cấp](https://docs.google.com/spreadsheets/d/1BySt_y_uFqUtiPWnoChK2a1I7G6Zbn_IzEJGdSrrexU/edit).

- Workbook tải trực tiếp khớp giá trị các ô của các sheet tham khảo trong `data/Dữ liệu tham khảo - Phần mềm Điều vận.xlsx`.
- Ba template XLSX đề xuất, thanh toán và bảng kê khớp giá trị các ô và vùng gộp của sheet tương ứng trong workbook trực tiếp. Đây là kiểm tra template; chưa phải chứng nhận mọi file xuất với mọi số dòng đều giống bản mẫu khi in.
- Checklist đã seed Daily 30 mục và Weekly 16 mục từ sheet nội dung. Phiếu Daily in bổ sung nhóm giấy tờ từ lịch sử Weekly/kho chứng từ.
- Đã có PDF/XLSX. PDF dựng lại bằng Blade/DomPDF, cần xem bản in A4 để xác nhận bố cục, ngắt trang và chữ ký; chưa đối chiếu hình ảnh từng trang với mẫu Google Sheets.

| Yêu cầu | Hiện trạng |
|---|---|
| Daily/Weekly trên app tài xế | Có, có nháp, tự lưu, xác nhận và gửi phiếu |
| Cấu hình giờ nhắc, thứ trong tuần | Có; scheduler chạy 5 phút/lần, có cache chống trùng và báo quá hạn cho điều phối |
| Mốc thời gian chính xác | Server ghi `started_at`, `checked_at`, `submitted_at`; thời gian máy khách lưu riêng để đối chiếu |
| NG kèm giải thích/ảnh | Có kiểm tra khi nộp theo cấu hình từng mục; Critical NG tạo kết quả fail và cảnh báo |
| Tài xế nhập chi phí theo xe | Có; hỗ trợ tạo mới hoặc gắn chi phí đã khai |
| Đề xuất / thanh toán / tạm ứng / hoàn ứng / bảng kê | Có các loại hồ sơ và mã DX/TT/TU/HU/BK |
| In, ký, scan, ghi nhận thanh toán | Có các API và màn hình liên quan; còn các vấn đề dưới đây |

`checked_at` phản ánh lúc server xử lý thay đổi kết quả mục kiểm tra; với autosave, nó có thể muộn hơn thao tác bấm trên thiết bị. Đây không phải bằng chứng thời điểm kiểm tra xe ngoài thực tế. Cần thống nhất ý nghĩa này khi nghiệm thu yêu cầu “mốc thời gian thực hiện”.

## Các lỗi đã tái hiện

P1: cần sửa trước khi nghiệm thu. P2: cần xử lý để luồng vận hành đầy đủ, tin cậy.

### F01 — P1: Dùng chung bộ đếm giới hạn request, có thể chặn gửi phiếu

Nguồn: `routes/api/spa/driver-mutate.php:73`, `routes/api/spa/driver-read.php` và middleware giới hạn request bao ngoài.

Các middleware `throttle:N,1` dùng cùng chữ ký người dùng. Request tự lưu/đọc và gửi phiếu ảnh hưởng chung bộ đếm, dù được cấu hình số lần khác nhau.

Đã tái hiện qua HTTP test:

1. Tạo checklist, thực hiện 16 lần PATCH tự lưu trong một phút, rồi nộp đầy đủ các mục: nhận HTTP 429.
2. Tạo đề xuất, GET chi tiết 35 lần trong một phút, rồi nộp: nhận HTTP 429.

Hướng sửa: tách named limiter theo mục đích, kiểm tra cả middleware bao ngoài để tránh cộng đếm chồng. Autosave và gửi phiếu phải có hạn mức độc lập.

### F02 — P1: Mất thông tin chung khi tự lưu checklist thất bại

Nguồn: `resources/js/src/views/driver/DriverChecklistFormView.vue`, hàm `flushSave()` và `submit()`.

Trước request, `headerDirty` bị xóa; khi request lỗi, chỉ khôi phục các item bẩn. `flushSave()` bắt lỗi rồi trả về bình thường nên bước gửi vẫn tiếp tục.

Đã tái hiện bằng Chromium, giao diện thật và API giả lập:

1. Mở checklist đã đánh dấu đủ các mục OK.
2. Nhập ghi chú chung; cho PATCH tự lưu trả HTTP 500.
3. Bấm xác nhận và nộp.
4. POST submit chỉ chứa `lock_version`, `confirm`, `client_submitted_at`, không còn `note`; giao diện vẫn hiện kết quả đã nộp.

Ảnh hưởng tương tự đối với tuyến/cơ sở, giờ khởi hành, người đi cùng và người kiểm tra vì cùng cơ chế `headerDirty`.

Hướng sửa: phục hồi trạng thái bẩn khi thất bại, giữ các chỉnh sửa mới trong lúc request đang chạy, bảo đảm gửi đủ dữ liệu chưa lưu hoặc dừng gửi và báo lỗi rõ ràng.

### F03 — P1: Một chi phí có thể xuất hiện hai lần trong cùng hồ sơ

Nguồn: `app/Services/Expenses/ExpenseDocumentService.php`, `bindTripCost()` và `assertCostNotHeldElsewhere()`.

Tạo chi phí 100.000 đồng, POST hồ sơ có hai dòng cùng `trip_cost_id`: API chấp nhận, tổng hồ sơ 200.000 đồng trong khi sổ chỉ có một chi phí 100.000 đồng.

Hướng sửa: chặn trùng `trip_cost_id` trong cùng hồ sơ ở validation/service; kiểm tra lại khi cập nhật và nộp.

### F04 — P1: Hủy hồ sơ cha chưa hủy bảng kê con

Nguồn: `app/Services/Expenses/ExpenseDocumentService.php:249`, `cancel()` và `deleteOwnedCosts()`.

1. Tạo TT có một dòng không hóa đơn và gửi.
2. Hệ thống tạo BK con trạng thái submitted.
3. Hủy TT: cha thành cancelled, BK con vẫn submitted, chi phí do cha tạo vẫn tồn tại vì BK đang giữ liên kết.

Hướng sửa: xử lý trạng thái cha/con trong cùng transaction; chỉ giải phóng/xóa chi phí đủ điều kiện sau khi xử lý các liên kết. Nếu con đã ký/thanh toán thì cần chặn hoặc áp dụng quy tắc nghiệp vụ rõ ràng.

### F05 — P1: Định danh xe trên dòng hồ sơ có thể lệch xe của chi phí gốc

Nguồn: `app/Services/Expenses/ExpenseDocumentService.php:359`, `bindTripCost()`.

Tạo chi phí cho xe B, tạo hồ sơ có xe mặc định A và dòng chỉ chứa `trip_cost_id` của chi phí đó: dòng hồ sơ nhận A, chi phí trong sổ vẫn thuộc B.

Đây là thiếu kiểm tra tính nhất quán phía API. Bộ chọn trên giao diện thường truyền thông tin xe đầy đủ, nên không có nghĩa mọi lần chọn qua giao diện đều bị sai.

Hướng sửa: xe của dòng liên kết phải được xác định từ chi phí gốc; chặn payload mâu thuẫn. Có thể cho phép hồ sơ nhiều xe nếu nghiệp vụ cho phép, nhưng từng dòng phải đúng xe gốc.

### F06 — P2: Chi phí gốc không hóa đơn có thể bị đổi thành có hóa đơn trên hồ sơ

Nguồn: `lineValues()` và `bindTripCost()` trong cùng service.

Khi API chỉ nhận `trip_cost_id`, `has_invoice` mặc định true và không được đồng bộ từ chi phí gốc. Đã tái hiện: chi phí gốc không hóa đơn nhưng dòng hồ sơ có hóa đơn, nộp TT không sinh BK.

Giao diện hiện có sao chép thông tin khi chọn chi phí; API vẫn cần tự bảo đảm dữ liệu khi chỉ nhận ID hoặc payload thiếu trường.

Hướng sửa: đồng bộ trạng thái hóa đơn và thông tin người bán từ chi phí gốc, quy định rõ những trường cho phép sửa.

### F07 — P2: BK tự sinh bỏ qua validation bắt buộc

Nguồn: `syncNoInvoiceStatement()` và `assertSubmittable()` trong `ExpenseDocumentService`.

Gửi TT với dòng không hóa đơn nhưng thiếu tên người bán và ngày mua: API chấp nhận, tự tạo BK submitted có các trường null. BK được tạo trực tiếp lại yêu cầu các trường này.

Hướng sửa: kiểm tra các dòng sẽ chuyển thành BK trước khi chốt hồ sơ; dùng chung quy tắc validation giữa BK trực tiếp và BK tự sinh.

### F08 — P2: Phiếu Daily có thể in thông tin Weekly của xe khác hoặc tương lai

Nguồn: `app/Services/Checklist/ChecklistPrintService.php:235`, `paperworkRows()`.

Query Weekly theo tài xế nhưng chưa lọc cùng xe và chưa giới hạn thời điểm trước phiếu Daily. Đã tái hiện Daily xe A ngày 05/10 lấy thông tin giấy tờ từ Weekly xe B ngày 08/10.

Hướng sửa: lọc theo xe phù hợp, giới hạn thời gian lịch sử, phân biệt giấy tờ tài xế và giấy tờ xe. Xem xét snapshot thông tin giấy tờ để in lại phiếu cũ không thay đổi theo dữ liệu mới.

### F09 — P2: Báo quá hạn qua nửa đêm bị bỏ sót

Nguồn: `app/Services/Checklist/ChecklistReminderService.php:29`.

Cấu hình nhắc 23:50, quá hạn sau 20 phút: lúc 23:52 gửi nhắc thành công; hôm sau 00:12 không gửi tổng hợp dù còn tài xế chưa nộp và có điều phối nhận. Service chỉ tính slot của ngày hiện tại.

Ngoài ra, cửa sổ xử lý chỉ 5 phút: scheduler gián đoạn quá cửa sổ sẽ không gửi bù. Đây là giới hạn suy ra trực tiếp từ mã, chưa mô phỏng server mất cron.

Hướng sửa: tính lần đến hạn theo ngày gốc, xử lý slot hôm trước khi cần, có cơ chế bắt kịp khi scheduler khởi động lại và chống gửi trùng theo đúng kỳ.

## Các điểm cần chốt khi triển khai

1. **Lưu file của hai phân hệ này vẫn có binary trong DB.** `app/Services/Attachments/AttachmentStoreService.php` lưu ảnh checklist/scan chi phí vào disk `local` (`storage/app/attachments/...`) đồng thời ghi `attachments.file_binary`. Chưa dùng disk `compliance` và `COMPLIANCE_STORAGE_ROOT` của luồng giấy tờ xe đã chỉnh trước đó. Vì vậy không thể kết luận mọi file của hệ thống đều chỉ còn đường dẫn trong DB. Nếu áp dụng cùng chính sách lưu file, cần chỉnh service này và có lộ trình cho dữ liệu cũ.
2. **Tài khoản chỉ có role accountant chưa vào được back-office.** Có `expense.view/manage` nhưng lớp truy cập app điều vận yêu cầu role khác. Tài liệu module đã ghi nhận giới hạn này; cần kiểm tra tài khoản kế toán thực tế hoặc chỉnh chính sách truy cập.
3. **Nhắc nhở cần scheduler, worker và cấu hình push.** Lệnh đã được đăng ký 5 phút/lần. Notification dùng queue cấu hình `dispatch.notifications_queue_default` (mặc định `default`); Web Push còn phụ thuộc cấu hình server và đăng ký nhận thông báo trên thiết bị. Chưa xác nhận các điều kiện này trên production.
4. **Nhắc theo kỳ, không theo từng ca/xe.** Có ít nhất một phiếu đã nộp trong ngày/tuần được coi là hoàn thành kỳ của tài xế. Nếu yêu cầu mỗi xe/mỗi ca đều bắt buộc làm thì cần bổ sung quy tắc.
5. **Thời điểm hiển thị nhắc có độ trễ.** Scheduler hiện chạy 5 phút/lần, chưa đáp ứng thông báo chính xác từng phút. Cần thống nhất mức trễ chấp nhận được.
6. **Critical NG cảnh báo nhưng chưa chặn nhận/chạy chuyến.** Đây là hành vi hiện tại, cần thống nhất với vận hành.
7. **Có thể ghi nhận paid từ submitted.** Scan ký chưa bắt buộc trong mọi trường hợp. Cần xác nhận có cho phép kế toán bỏ qua bước scan hay phải bắt buộc signed trước paid.

## Bằng chứng kiểm tra

- Bộ test sẵn có: **36 test, 187 assertion đạt**, bao gồm checklist, nhắc cơ bản, quyền, luồng chi phí, khóa dữ liệu, xuất PDF/XLSX và mã chứng từ.
- Thêm **9 diagnostic probe** tái hiện F01 và F03–F09 (F01 có hai trường hợp). Probe chủ động xác nhận hành vi lỗi hiện tại; kết quả chạy thành công không có nghĩa các luồng này đạt nghiệm thu.
- Kiểm tra riêng mốc thời gian: gửi thời gian máy khách năm 2020, server vẫn ghi thời điểm kiểm thử năm 2026 cho thời gian bắt đầu, đánh dấu và nộp phiếu.
- Tổng lượt chạy cuối: **47 test, 302 assertion**, gồm 36 test sẵn có, 9 probe lỗi, 1 test mốc thời gian và 1 bước tạo fixture cho kiểm tra trình duyệt. Các probe/fixture là công cụ chẩn đoán tạm thời, không đưa vào bộ test nghiệm thu của dự án.
- Chromium mobile 390×844 tái hiện F02 qua giao diện thật, mạng API giả lập. Chưa phải E2E với backend/server production.
- Tất cả dữ liệu API kiểm thử nằm trong SQLite in-memory; notification được giả lập ở các phép thử nhắc. Không dùng các lệnh này để phát thông báo thật tới tài xế.

## Cách test nghiệm thu sau khi sửa

### Checklist

1. Đăng nhập tài xế A, tạo Daily đúng xe; đánh dấu đủ 30 mục, đợi tự lưu rồi tải lại: dữ liệu còn nguyên. Làm tương tự Weekly 16 mục.
2. So sánh giờ bắt đầu, checked_at, giờ nộp với giờ server/VN. Đổi đồng hồ thiết bị: mốc nghiệp vụ vẫn phải theo server.
3. Cho một mục NG, thử gửi thiếu ghi chú; với mục yêu cầu ảnh, thử thiếu ảnh. Phải bị chặn và chỉ rõ mục cần bổ sung. Điền đủ rồi gửi: kết luận và thông báo đúng.
4. DevTools chặn PATCH tự lưu hoặc dùng mock lỗi 500, nhập ghi chú/tuyến, rồi bấm gửi: phải giữ dữ liệu và báo lỗi hoặc gửi đủ dữ liệu mới. Tải lại để xác nhận không mất thông tin.
5. Thực hiện nhiều lần tự lưu trong một phút rồi nộp: không bị 429 do dùng nhầm bộ đếm.
6. Tài xế B không đọc/sửa được phiếu của A. Điều phối xem được trạng thái và review được theo quyền.
7. Cấu hình một slot thử gần giờ hiện tại cho tài khoản thử; theo dõi scheduler, queue, notification trong app và trên thiết bị. Nộp đúng kỳ rồi kiểm tra không còn bị nhắc. Chạy nhắc lại không trùng.
8. Kiểm tra slot 23:50, quá hạn sau 20 phút; kỳ ngày/tuần, thời điểm cuối tuần và scheduler khởi động lại.
9. In Daily khi cùng tài xế có Weekly của hai xe và có Weekly mới hơn ngày Daily: dữ liệu giấy tờ phải đúng xe, đúng thời điểm lịch sử.

### Hồ sơ chi phí

1. Tạo DX cho xe A, nhập nhiều dòng, kiểm tra tổng và số tiền bằng chữ. Lưu, mở lại, xuất PDF/XLSX và đối chiếu mẫu in A4.
2. Thử TT, TU, HU, BK riêng; kiểm tra mã chứng từ, phương thức thanh toán, thông tin tài khoản và trường bắt buộc.
3. Gắn chi phí đã khai của xe B: dòng phải đúng xe B; payload mâu thuẫn phải bị chặn hoặc được chuẩn hóa nhất quán. Gắn cùng chi phí hai lần: phải bị từ chối.
4. Gắn chi phí không hóa đơn chỉ bằng ID: trạng thái hóa đơn và người bán phải giữ đúng. Thiếu tên người bán/ngày mua phải chặn trước khi sinh BK submitted.
5. Gửi TT có BK con, kiểm tra khóa sửa/xóa chi phí. Hủy cha: trạng thái con và số liệu sổ theo xe phải nhất quán; không xóa chi phí đã xác nhận hoặc còn hồ sơ hợp lệ khác giữ.
6. Từ chối hồ sơ, tài xế sửa và nộp lại; không sinh trùng chi phí/BK. Upload scan, ghi nhận thanh toán; kiểm tra quyền và lịch sử thao tác.
7. Xuất hồ sơ có nhiều dòng, ghi chú dài và tên xe dài: bảng không đè chữ ký, không mất dòng, tổng đúng. Mở XLSX bằng Excel và PDF ở chế độ in A4.
8. Upload một ảnh checklist và một scan chi phí; kiểm tra disk/path và `file_binary` để xác nhận đúng chính sách lưu trữ đã thống nhất.

### Lệnh kiểm tra trên server, chỉ đọc

Chạy từ thư mục dự án. Các lệnh dưới đây không phát nhắc tới tài xế:

```bash
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan schedule:list
sudo -u dieuv2595 /usr/local/lsws/lsphp81/bin/php artisan queue:failed
ps -eo user,args | grep '[a]rtisan queue:work'
sudo crontab -u dieuv2595 -l
sudo crontab -u quangtm -l
```

Đối chiếu cron hiện có: phải có cơ chế gọi `artisan schedule:run` mỗi phút bằng PHP phù hợp; có thể được cấu hình ở root, tài khoản deploy, systemd hoặc panel. Không có crontab ở một user chưa đủ kết luận không có scheduler.

Không chạy tùy tiện `checklist:remind-drivers --at=...` trên DB đang phục vụ thật: `--at` giả lập thời gian nhưng lệnh vẫn có thể gửi thông báo thật và ghi cache chống trùng.

Chạy bộ test tự động ở môi trường local/test, không chạy PHPUnit trên production:

```powershell
php vendor/bin/phpunit tests/Feature/Checklist tests/Feature/Expenses tests/Feature/DocumentCodeGeneratorTest.php tests/Unit/Support/VietnameseMoneyWordsTest.php tests/Unit/Support/ChecklistResultCalculatorTest.php
```

Thứ tự sửa đề xuất: F01–F05 → F06–F09 → đồng bộ chính sách lưu file/quyền kế toán → xác nhận thông báo trên thiết bị và bản in thực tế.
