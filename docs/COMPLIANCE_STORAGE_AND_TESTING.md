# Lưu trữ và kiểm thử chứng từ xe / tài xế

## Cơ chế hiện tại

Scan xe/tài xế (đăng kiểm, bảo hiểm, GPLX, cả các phiên bản trong lịch sử) chỉ ghi nội dung vào kho file private. Database lưu metadata (`disk`, `path`, `original_name`, `size_bytes`, `mime_type`, `sha256`) và thông tin nghiệp vụ/lịch sử, không ghi binary cho upload mới.

Phạm vi chuyển đổi: attachment của `VehicleComplianceDocument`, `DriverComplianceDocument` và hai tên alias tương ứng. Phiếu điều vận, chi phí, POD và loại attachment khác giữ cơ chế hiện có. Không bỏ cột `attachments.file_binary` vì các loại khác vẫn dùng.

Ứng dụng ưu tiên đọc file chứng từ trên disk. Trong giai đoạn chuyển, binary cũ vẫn là fallback nếu không tìm thấy file. Sau prune, chứng từ có `file_binary = NULL`; mất file cần khôi phục backup.

## Cấu hình production

Ví dụ Linux, thay `www-data` bằng tài khoản thực tế chạy PHP/CLI:

```sh
sudo install -d -o www-data -g www-data -m 0700 /srv/va-dieu-van-private
```

Trong `.env`:

```dotenv
COMPLIANCE_FILESYSTEM_DISK=compliance
COMPLIANCE_STORAGE_ROOT=/srv/va-dieu-van-private
```

Windows dùng đường dẫn tuyệt đối như `D:/va-dieu-van-private` và cấp ACL đọc/ghi cho tài khoản PHP. Kho phải nằm ngoài thư mục công khai, tồn tại qua deploy, không tạo symlink công khai. Nếu nhiều server/worker, tất cả phải nhìn thấy cùng kho (shared volume hoặc disk private chung); không dùng filesystem tạm của container.

Mặc định khi chưa đặt root: `storage/app`. Cấu hình độc lập với `FILESYSTEM_DISK`. Ứng dụng từ chối disk có visibility `public`. Không cần `storage:link` cho chứng từ private.

Chạy lệnh bằng tài khoản PHP hoặc tài khoản có cùng quyền, ví dụ `sudo -u www-data php artisan ...`; tránh chạy root tạo file PHP không đọc được. Không cần migration schema mới cho thay đổi này.

## Chuyển dữ liệu cũ trên server

Thực hiện thử trên bản sao staging trước. Sao lưu database đầy đủ có binary (MySQL dump dùng `--hex-blob`) và các thư mục file đang được tham chiếu; kiểm tra có thể khôi phục. Không chạy SQL xóa binary trực tiếp.

1. Dừng ghi bằng maintenance, dừng scheduler/worker có thể sửa attachment theo quy trình server. `artisan down` không tự dừng queue worker:

```sh
php artisan down
```

2. Deploy code mới trên tất cả instance, cấu hình root bền vững, giữ nguyên nguồn cũ, rồi làm mới cache:

```sh
php artisan config:cache
```

Nếu đổi root của **cùng một disk**, sao chép hai thư mục `attachments/vehicle_compliance_documents` và `attachments/driver_compliance_documents` từ root cũ sang root mới, giữ cấu trúc trước khi chạy lệnh. Không bỏ thư mục `local` cũ. Nếu dùng release directory, nguồn cũ phải còn đọc được qua cấu hình disk hoặc đã được sao chép vào root mới.

3. Kiểm tra nguồn, không ghi file/database:

```sh
php artisan compliance:migrate-files
```

Lệnh quét cả hiện hành và lịch sử, đọc từng file để hạn chế RAM. Dùng binary khớp dung lượng/SHA-256; nếu thiếu/hỏng thì thử disk cũ, kể cả nhãn `public` sai hoặc tiền tố `storage/`. Không có nguồn hợp lệ: báo `Attachment #<id>`, giữ nguyên bản ghi, exit code khác 0. Nguồn cũ chưa có hash được tính SHA-256 mới; nên đối chiếu thêm file gốc của hồ sơ quan trọng.

4. Khi dry-run không có lỗi, xuất file và kiểm tra:

```sh
php artisan compliance:migrate-files --apply
php artisan compliance:migrate-files --verify
```

`--apply` ghi file private, đọc lại kiểm tra dung lượng/SHA-256 rồi mới cập nhật disk/path/hash; **giữ binary**. `--verify` chỉ kiểm tra đúng disk/path trong DB, không fallback. Kết quả phải `failed=0`. Chạy lại không sinh bản sao nếu file đã đúng; đích khác nội dung không bị ghi đè.

5. Sau khi xác minh và có backup, xóa binary bằng bước riêng:

```sh
php artisan compliance:migrate-files --apply --prune-binary
php artisan compliance:migrate-files --verify
```

Mỗi bản ghi được khóa khi cập nhật. Prune chỉ xóa binary nếu file đích khớp dung lượng và SHA-256. File thiếu/hỏng hoặc chưa export: giữ binary, báo lỗi. Có thể chạy lại sau gián đoạn. Có thể có bản ghi thành công và bản ghi lỗi trong cùng lượt; không bỏ qua exit code lỗi.

Hoàn tất khi **`failed=0` và `binary_remaining_on_success=0`**. Bộ đếm binary chỉ tính bản ghi thành công nên không coi số 0 là hoàn tất khi có lỗi. Lệnh không xóa nguồn file cũ, giữ chúng để rollback; chỉ dọn bản sao sau khi xác nhận không còn bản ghi tham chiếu và đã thử khôi phục backup. Không xóa cả `storage/app` vì còn dữ liệu khác.

6. Mở ứng dụng, khởi động worker/scheduler lại theo trình quản lý service trên server:

```sh
php artisan queue:restart
php artisan up
```

Worker đã dừng qua Supervisor/systemd cần khởi động lại qua trình quản lý đó. Nếu có bước lỗi, giữ maintenance, sửa quyền/khôi phục nguồn rồi chạy lại; không tiếp tục prune khi file có lỗi.

## Kiểm tra trên server

Kiểm tra cấu hình thực tế, file đích và số chứng từ còn binary (không xuất mật khẩu):

```sh
php artisan tinker --execute="dump(config('compliance.disk'), config('filesystems.disks.'.config('compliance.disk').'.root'));"
php artisan compliance:migrate-files --verify
php artisan tinker --execute="dump(App\Models\Attachment::query()->whereIn('attachable_type', App\Models\Attachment::COMPLIANCE_TYPES)->whereNotNull('file_binary')->count());"
```

Lệnh đếm cuối phải là `0`. Sau đó:

1. Đăng nhập, mở Kho chứng từ, tải file cũ và file trong lịch sử.
2. Gia hạn hồ sơ test bằng PDF mới và hồ sơ khác bằng JPG/PNG; xem/tải tại chi tiết, popup xe và kho chứng từ.
3. Chạy lại verify và đếm binary: file mới được xác minh, binary vẫn bằng 0.
4. So sánh file tải về với file gốc (dung lượng/SHA-256). Tài khoản thiếu quyền phải bị chặn; URL `/storage/attachments/...` không mở được file mới ở kho private.
5. Reload kiểm tra hạn/lịch sử; thử deploy lại hoặc restart container trên staging để xác nhận file tồn tại lâu dài.

Verify đọc toàn bộ kho, nên chọn thời điểm phù hợp khi dữ liệu lớn. Không sửa/xóa file production để thử mất file; thử thiếu/hỏng file và restore backup trên staging.

## Backup và rollback

Sau prune, backup database **không chứa nội dung scan xe/tài xế**. Backup database và kho private cùng thời điểm; thử khôi phục hai phần rồi verify/tải file. File đính kèm khác vẫn có thể chứa binary trong database.

Đặt binary về NULL giảm dữ liệu logic; file MySQL/InnoDB trên ổ đĩa có thể chưa nhỏ lại ngay. Không tự chạy `OPTIMIZE TABLE` trong quy trình này; thu hồi dung lượng vật lý cần kế hoạch riêng theo tải/config database.

Trước prune có thể restore bản database/file trước chuyển hoặc dùng code hỗ trợ đọc disk. Sau prune, rollback đầy đủ cần restore database **và** kho file từ backup tương ứng. Rollback code không tự tạo lại binary.

## Test tự động và nghiệp vụ

Chỉ chạy tại môi trường phát triển/test theo `phpunit.xml`, không có config cache trỏ database thật. Không chạy PHPUnit trên production:

```sh
php vendor/bin/phpunit tests/Feature/ComplianceDocumentRenewalTest.php tests/Feature/ComplianceFileMigrationTest.php
```

Bộ test dùng SQLite trong RAM và storage giả lập: upload không ghi binary, đọc disk, tương thích binary cũ, dry-run, chạy lại, fallback nguồn cũ, chặn prune khi mất/hỏng đích, xung đột file, lỗi ghi, phân quyền và phạm vi attachment.

Nghiệp vụ: thiếu file/HTML/trên 10 MB, hạn mới trước hôm nay/bằng hạn cũ, ngày cấp sau hạn phải bị chặn; đổi loại tính lại cả hai hạn; xóa bản cuối xóa hạn; xóa bản gia hạn khôi phục bản trước; gia hạn GPLX. Không tự xóa hàng loạt hạn nhập tay của hồ sơ cũ.
