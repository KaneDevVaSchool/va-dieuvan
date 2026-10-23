# Nhập master data nhân sự HCNS

Lệnh nhập hồ sơ nhân sự và cơ cấu tổ chức vào database điều vận. HCNS là nguồn nhân sự duy nhất; tích hợp nhân sự CMS và lệnh đồng bộ cũ đã được gỡ. Không xóa tài khoản, không thay mật khẩu, không cập nhật người duyệt của phiếu đã tạo. Không sao chép CCCD, ngân hàng, địa chỉ hoặc email cá nhân từ Excel.

## Cách chạy

Chạy migration trên môi trường cần nhập, sau đó kiểm tra báo cáo trước khi nhập thật:

```powershell
php artisan migrate
php artisan hr:import-master-data "data/[HCNSxPCN] - [21_10_26] - MasterData Imported & Fixing .xlsx" --dry-run --report=storage/app/hr-import-preview.json
php artisan hr:import-master-data "data/[HCNSxPCN] - [21_10_26] - MasterData Imported & Fixing .xlsx" --grant-head-role --report=storage/app/hr-import-result.json
```

`--dry-run` chỉ đọc và kiểm tra, không thực hiện INSERT/UPDATE, không cấp role, không ghi audit vào DB. `--report` xuất JSON gồm số lượng, lỗi, cảnh báo và trạng thái đã nhập; báo cáo có mã NV và số dòng, cần lưu nội bộ.

Mặc định cảnh báo không chặn nhập, các tham chiếu chưa khớp để null và giữ nhãn gốc ở `hr_employees.organization_labels`. `--strict` chặn cả cảnh báo. Lỗi trùng mã khác nội dung, email trùng hoặc xung đột tài khoản luôn chặn toàn bộ lần nhập. Thay đổi được ghi trong một transaction và có audit; chạy lại cập nhật theo mã, không tạo trùng.

`--grant-head-role` bổ sung role `department_head` cho quản lý có trong danh mục phòng ban/bộ phận và danh sách nhân viên. Giữ các role và primary role đang có, không tự thu hồi quyền của quản lý cũ. Nếu bỏ tùy chọn này, chỉ nhập quan hệ quản lý; người chưa có quyền duyệt sẽ chưa được tự gán làm người duyệt. Cần seed RBAC trước khi dùng tùy chọn cấp quyền.

`--skip-user-conflicts` cho phép bỏ qua toàn bộ dòng nhân viên bị xung đột tài khoản (không tạo user hoặc hồ sơ nhân sự cho dòng đó). Báo cáo có `skipped_employees`. Tùy chọn này không bỏ qua lỗi dữ liệu Excel hoặc bất kỳ xung đột nào của 7 tài xế chính.

## Nối 7 tài xế chính

Mapping mã nhân viên → tên hồ sơ tài xế nằm trong `config/hr.php`. Lệnh chỉ nối các hồ sơ tài xế đã tồn tại này, giữ nguyên `drivers.id`, chuyến, checklist, thông tin GPLX và các thông tin hồ sơ khác:

| Mã NV | Hồ sơ tài xế |
|---|---|
| VA010006 | Đỗ Mạnh Hùng |
| VA010107 | Bùi Trung Hiếu |
| VA010936 | Nguyễn Tấn Tài |
| VA011384 | Nguyễn Huy Sang |
| VA011377 | Hồ Tuấn Kiệt |
| VA010502 | Dương Hoàng Trọng |
| VA011423 | Lưu Quốc Việt |

Tài khoản được đối chiếu theo mã/email như phần nhân sự, sau đó nối vào hồ sơ tài xế đã chỉ định. Nếu chưa có tài khoản nhưng có email công ty thì tạo tài khoản rồi nối. Bổ sung role `driver` khi thiếu, giữ các role đang có. Không tự thay thế một user khác đang gắn trên hồ sơ hoặc gắn một user cho hai tài xế. Thiếu tài xế chính trong file, thiếu hồ sơ hoặc mapping mơ hồ đều chặn nhập.

Nguyễn Huy Sang: email Excel hiện khớp user tên Nguyễn Hữu Sang. Khi nhập, tên user cập nhật theo Excel và liên kết tới hồ sơ Nguyễn Huy Sang; báo cáo ghi cảnh báo tên khác nhau. Lưu Quốc Việt: tạo user theo email Excel rồi nối hồ sơ sẵn có.

Trần Minh Quang là hồ sơ test, không nằm trong mapping tài xế, lệnh không sửa hồ sơ tài xế hoặc thu hồi role của user này. Đoàn Ngọc Hòa, Nguyễn Minh Sơn, Đặng Ngọc Lam chỉ được nhập nhân sự như các dòng khác, không được tạo hồ sơ tài xế hoặc cấp role `driver` theo chức danh.

Chạy với database hiện tại có các xung đột nhân sự không thuộc nhóm tài xế:

```powershell
php artisan hr:import-master-data "data/[HCNSxPCN] - [21_10_26] - MasterData Imported & Fixing .xlsx" --dry-run --skip-user-conflicts --report=storage/app/hr-import-preview.json
php artisan hr:import-master-data "data/[HCNSxPCN] - [21_10_26] - MasterData Imported & Fixing .xlsx" --skip-user-conflicts --grant-head-role --report=storage/app/hr-import-result.json
```

Có thể mapping tên cơ sở đã được xác minh bằng tùy chọn lặp lại (đích là tên hoặc mã cơ sở trong danh mục):

```powershell
php artisan hr:import-master-data "path/to/file.xlsx" --dry-run --branch-alias="Tên trong sheet nhân viên=Mã cơ sở đích"
```

Không tự tách cơ sở ghép thành một cơ sở đơn lẻ. Khi tên cơ sở trùng, ưu tiên cơ sở thuộc pháp nhân chính của nhân viên; nếu không có thì chỉ sử dụng tên khớp duy nhất trên toàn danh mục. Trường hợp còn mơ hồ giữ nhãn gốc và báo cảnh báo.

## Đối chiếu dữ liệu

- Danh mục nhận diện bằng mã phần mềm/mã danh mục; cấp bậc dùng số cấp. Cột được tìm theo header, có thể đổi thứ tự cột.
- Dòng danh mục trùng hoàn toàn được gộp và cảnh báo; dòng nhân viên trùng mã luôn là lỗi.
- Nhân viên nhận diện bằng `Mã nhân viên 1 *`. Mã/email được trim và chuẩn hóa hoa/thường.
- Đối chiếu cả mã NV và email công ty với `users`. Nếu hai khóa trỏ tới hai user khác nhau hoặc user đã thuộc mã khác, dừng để xử lý thủ công.
- Khớp mã NV nhưng email thay đổi: giữ email đăng nhập hiện tại, ghi email từ Excel vào hồ sơ và cảnh báo.
- Người chưa có user và có email công ty sẽ được tạo user với mật khẩu ngẫu nhiên, source `manual`, không có role mặc định. Người thiếu email vẫn có hồ sơ nhân sự; chỉ nối user nếu khớp mã NV.
- User cũ giữ nguyên ID, source, Google ID, mật khẩu, trạng thái hoạt động và quyền. Chỉ cập nhật tên/mã NV, điện thoại và phòng ban khi nguồn có dữ liệu. Không tự vô hiệu hóa user vắng mặt trong file.
- Quan hệ quản lý được gắn sau khi nhập đủ nhân viên. Một người có thể quản lý nhiều đơn vị.

Các bảng mới: `hr_companies`, `hr_branches`, `hr_levels`, `hr_job_titles`, `hr_divisions`, `hr_employees`. Bảng `departments` hiện có được bổ sung pháp nhân, email quản lý và user quản lý. Hồ sơ nhân sự có foreign key tới các danh mục; các tham chiếu chưa khớp được giữ trong `organization_labels`.

Trang tài khoản tài xế và phần tóm tắt hồ sơ dùng `work_info` từ API `/api/user`, chỉ đọc hồ sơ HCNS đã nhập, không fallback sang CMS hay phòng ban/mã nhân viên cũ trên tài khoản. Trụ sở lấy từ cơ sở trong Excel. Excel không có cột nơi làm việc riêng, nên hiển thị tên cơ sở HCNS cho mục này. Tham chiếu danh mục chưa khớp vẫn có thể hiển thị nhãn gốc HCNS trong `organization_labels`. Không có hồ sơ HCNS hoặc HCNS để trống thì để trống; không dùng dữ liệu cũ để lấp vào.

API không còn trả `cms_user_info`. Trang hồ sơ bỏ các form giấy tờ/ngân hàng/nhân sự cũ; thông tin công việc chỉ đọc từ HCNS. PATCH `/api/user` chỉ nhận điện thoại và ảnh đại diện; trường nhân sự khác bị từ chối bằng 422. Điện thoại cập nhật đồng thời trên tài khoản và hồ sơ HCNS nếu đã liên kết. Tìm người đề xuất và danh sách phân quyền cũng dùng HCNS.

Sau khi deploy thay đổi gỡ CMS, chạy `php artisan optimize:clear` và build frontend để bỏ cấu hình/UI cũ. Có thể gỡ các biến `CMS_DB_*` và `CMS_DATABASE_URL` khỏi cấu hình môi trường; ứng dụng không sử dụng chúng nữa. Giá trị `users.source` lịch sử được giữ nguyên để không thay đổi danh tính tài khoản. Không xóa database CMS bên ngoài hoặc các bảng nghiệp vụ.

## Gán trưởng đơn vị

`EmployeeManagerResolver` là điểm dùng chung: lấy quản lý bộ phận trước, sau đó quản lý phòng ban từ hồ sơ HCNS. Chỉ trả về user đang hoạt động, có role `department_head`, khác người đề xuất. Nếu quản lý bộ phận không đủ điều kiện thì thử quản lý phòng ban HCNS. User chưa có hồ sơ HCNS không được tự gán quản lý theo phòng ban cũ trên tài khoản.

Portal tạo phiếu mới gợi ý sẵn người duyệt; người dùng vẫn có thể chọn người khác trong danh sách có quyền. API tạo phiếu portal/điều vận tự lấy quản lý khi không truyền `dept_head_user_id`, ngoại trừ đưa đón. Khi điều vận tạo thay người khác, resolver dùng người đề xuất thực tế. API portal vẫn yêu cầu chọn thủ công nếu không tìm được người đủ điều kiện.

API tìm trưởng đơn vị hỗ trợ `suggested=1` và giữ kiểu trả về danh sách cũ. Endpoint điều vận hỗ trợ thêm `requester_id` cho dispatcher/admin; endpoint portal luôn dùng user đăng nhập.

Người duyệt được lưu vào `dispatch_requests.assigned_dept_head_id`. Nhập lại master data không thay người duyệt của phiếu cũ. Luồng tạo kế hoạch CLB định kỳ vẫn hoãn gán trưởng đơn vị tới bước xử lý phiếu chuyến như trước.

## Các điểm cần rà soát của file 21/10/2026

- 612 nhân viên, 566 email công ty, 46 hồ sơ thiếu email.
- Sheet chức danh có 701 dòng, nhưng chỉ 700 mã duy nhất: dòng 702 trùng hoàn toàn dòng 14.
- Dòng nhân viên 211 và 594 thiếu pháp nhân/phòng ban.
- Dòng 132 và 211 có mã chức danh chưa tồn tại trong danh mục.
- 21 nhân viên dùng tên cơ sở ghép “Tiểu học, THCS Việt Mỹ Phú Định”, chưa có mapping duy nhất.
- 66 nhân viên có nhãn cấp bậc chưa khớp danh mục cấp bậc.
- 5 phòng ban và 5 bộ phận chưa có email quản lý.

Các con số trên phản ánh file đã kiểm tra. Báo cáo mỗi lần chạy là kết quả chính thức cho file và database tại thời điểm đó.
