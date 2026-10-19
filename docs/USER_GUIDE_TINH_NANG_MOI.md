# Hướng dẫn sử dụng các tính năng mới

**Phần mềm điều vận — cập nhật ngày 08/10/2026**

Tài liệu chỉ hướng dẫn những tính năng mới được bổ sung từ commit đầu tiên của MinhQuang2612 và các tính năng đang có trong phần thay đổi chưa commit trên nhánh `update_feature`.

| Trạng thái mã nguồn | Tính năng |
|---|---|
| Đã commit | Gia hạn giấy tờ xe/tài xế; kho chứng từ tập trung; thêm người dùng thủ công; cải thiện đồng bộ người dùng CMS; import xe/tài xế từ Excel bàn giao; dọn dữ liệu test. |
| Đang chưa commit | Checklist tài xế; giám sát và nhắc checklist; hồ sơ đề xuất/thanh toán/tạm ứng/hoàn ứng; bảng kê không hóa đơn; sổ chi phí theo xe; cấu hình và xuất biểu mẫu. |

> Hướng dẫn được đối chiếu với mã nguồn hiện tại. Trạng thái “đã commit” không đồng nghĩa đã triển khai lên hệ thống đang sử dụng. Các phần chưa commit chỉ xuất hiện khi phiên bản tương ứng đã được triển khai, khởi tạo dữ liệu và bật tính năng.

## Mục lục

1. [Điều kiện sử dụng và quyền truy cập](#1-điều-kiện-sử-dụng-và-quyền-truy-cập)
2. [Kho chứng từ và gia hạn giấy tờ](#2-kho-chứng-từ-và-gia-hạn-giấy-tờ)
3. [Thêm người dùng thủ công và đồng bộ CMS](#3-thêm-người-dùng-thủ-công-và-đồng-bộ-cms)
4. [Checklist dành cho tài xế](#4-checklist-dành-cho-tài-xế)
5. [Theo dõi và cấu hình checklist](#5-theo-dõi-và-cấu-hình-checklist)
6. [Lập và theo dõi hồ sơ chi phí](#6-lập-và-theo-dõi-hồ-sơ-chi-phí)
7. [Xử lý hồ sơ dành cho kế toán](#7-xử-lý-hồ-sơ-dành-cho-kế-toán)
8. [Sổ chi phí theo xe và cấu hình mẫu in](#8-sổ-chi-phí-theo-xe-và-cấu-hình-mẫu-in)
9. [Công cụ dành cho người vận hành hệ thống](#9-công-cụ-dành-cho-người-vận-hành-hệ-thống)
10. [Các tình huống thường gặp](#10-các-tình-huống-thường-gặp)

## 1. Điều kiện sử dụng và quyền truy cập

### 1.1. Ai sử dụng phần nào?

| Người sử dụng | Phần hướng dẫn liên quan |
|---|---|
| Tài xế | Làm checklist; lập, nộp, sửa hồ sơ bị trả lại; tải mẫu in; gửi bản scan đã ký. |
| Điều phối | Theo dõi checklist; ghi nhận xử lý; cấu hình checklist và lịch nhắc; xem hồ sơ chi phí. |
| Kế toán | Kiểm tra hồ sơ chi phí; trả lại hồ sơ; tải bản scan; xác nhận thanh toán; xem sổ chi phí; cấu hình mẫu in. |
| Quản trị | Quản lý giấy tờ và người dùng theo quyền được cấp; bật tính năng, cấp quyền; sử dụng các phần quản trị được phân công. |
| Người vận hành hệ thống | Chạy lệnh import, đồng bộ CMS và dọn dữ liệu trên máy chủ. |

Các nút thao tác xuất hiện theo quyền và trạng thái dữ liệu. Có quyền xem không đồng nghĩa có quyền sửa hoặc xử lý.

### 1.2. Quyền của các module mới

| Quyền | Cho phép |
|---|---|
| `checklist.submit` | Tài xế tạo, lưu nháp và nộp checklist của mình. |
| `checklist.view` | Xem danh sách, chi tiết, tuân thủ và ghi nhận đã xem checklist. |
| `checklist.manage` | Sửa cấu hình checklist và khung giờ nhắc. |
| `expense.submit` | Lập và thao tác với hồ sơ chi phí của mình theo trạng thái cho phép. |
| `expense.view` | Xem hồ sơ chi phí và sổ chi phí theo xe. |
| `expense.manage` | Trả lại, xác nhận thanh toán và các thao tác xử lý/cấu hình hồ sơ. |
| `resource.vehicle.manage` / `resource.driver.manage` | Quản lý, gia hạn giấy tờ xe / tài xế tương ứng. |
| `system.user_roles.manage` | Thêm người dùng thủ công và quản lý vai trò. |

Checklist và hồ sơ chi phí có công tắc riêng. Nếu không thấy menu, quản trị cần kiểm tra tính năng đã bật và tài khoản đã được cấp quyền hay chưa.

**Lưu ý cho kế toán:** ở phiên bản hiện tại, tài khoản chỉ có vai trò `accountant` chưa vào được ứng dụng quản trị điều vận. Quản trị cần kiểm tra và cấp vai trò truy cập ứng dụng phù hợp, ngoài các quyền xử lý chi phí.

### 1.3. Lối vào các màn hình

Đường dẫn dưới đây là phần phía sau tên miền hệ thống.

| Màn hình | Đường dẫn |
|---|---|
| Kho chứng từ | `/mng/resources/documents` |
| Gán vai trò tài khoản / thêm người dùng | `/mng/system/user-roles` |
| Checklist của tài xế | `/driver/checklists` |
| Hồ sơ chi phí của tài xế | `/driver/expenses` |
| Giám sát checklist | `/mng/checklists` |
| Cấu hình checklist | `/mng/checklists/settings` |
| Hồ sơ chi phí xe dành cho nhân viên | `/mng/expenses` |
| Sổ chi phí theo xe | `/mng/expenses/ledger` |
| Cấu hình mẫu in | `/mng/expenses/settings` |

## 2. Kho chứng từ và gia hạn giấy tờ

### 2.1. Tra cứu trong kho chứng từ

**Mục đích:** xem giấy tờ của nhiều xe và tài xế tại một nơi, ưu tiên xử lý giấy tờ sắp hết hạn hoặc đã hết hạn.

1. Vào **Kho chứng từ** trong ứng dụng quản trị.
2. Chọn nhóm giấy tờ **xe** hoặc **tài xế**.
3. Chọn loại chứng từ cần tra cứu, hoặc để tất cả.
4. Chọn tình trạng hạn và trạng thái chứng từ nếu cần.
5. Nhập từ khóa: tiêu đề, số chứng từ, biển số xe, tên hoặc số điện thoại tài xế.
6. Kiểm tra chủ sở hữu, ngày hết hạn và file đính kèm trong kết quả.
7. Dùng thao tác xem trước/tải về của file. Muốn quản lý hoặc gia hạn, mở liên kết đến hồ sơ xe/tài xế tương ứng.

| Tình trạng | Ý nghĩa |
|---|---|
| Đã hết hạn | Ngày hết hạn đã qua. |
| Sắp hết hạn | Còn hạn nhưng sẽ hết hạn trong vòng 30 ngày. |
| Còn hạn | Ngày hết hạn còn hơn 30 ngày. |
| Chưa có hạn | Chứng từ chưa được khai báo ngày hết hạn. |
| Đã thay thế | Bản cũ đã có bản mới thay thế; giữ lại để tra cứu. |

Mặc định kho ưu tiên các bản đang hiệu lực. Nếu cần tìm bản cũ, đổi bộ lọc trạng thái. Kho không hiển thị giấy tờ của xe/tài xế đã bị xóa mềm.

### 2.2. Gia hạn giấy tờ xe hoặc tài xế

**Chuẩn bị:** bản scan mới và ngày hết hạn mới. File được hỗ trợ: PDF, JPG/JPEG, PNG, WebP, HEIC/HEIF, Word hoặc Excel; tối đa **10 MB**.

1. Mở **Chi tiết xe** hoặc **Chi tiết tài xế**.
2. Tìm giấy tờ hiện hành cần thay thế trong phần giấy tờ.
3. Chọn **Gia hạn**.
4. Trong hộp thoại **Gia hạn / cập nhật chứng từ mới**, tải bản scan mới.
5. Nhập **Ngày hết hạn** mới.
6. Bổ sung ngày cấp, số chứng từ, tiêu đề và ghi chú nếu có.
7. Chọn **Lưu chứng từ mới**.
8. Kiểm tra thông báo thành công, ngày hết hạn và file mới trên hồ sơ.

**Điều kiện lưu:**

- Phải có file mới và ngày hết hạn mới.
- Nếu bản hiện tại có ngày hết hạn, ngày mới phải sau ngày cũ.
- Ngày hết hạn mới không được trước hôm nay, tính theo múi giờ Việt Nam. Giấy tờ hết hạn ngay hôm nay vẫn được chấp nhận.
- Ngày cấp, nếu nhập, không được sau ngày hết hạn.
- Không gia hạn tiếp từ một bản đã bị thay thế; hãy chọn bản đang hiệu lực.

Sau khi lưu, hệ thống tạo bản mới, chuyển bản cũ sang **Đã thay thế** và giữ file cũ. Những loại giấy tờ có liên kết với hạn trên hồ sơ xe/tài xế sẽ được cập nhật hạn tương ứng.

**Ví dụ:** đăng kiểm hiện tại hết hạn 20/10/2026. Sau khi nhận bản mới, tải file và nhập hạn 20/04/2027. Bản mới trở thành bản hiện hành; bản hết hạn 20/10/2026 vẫn còn trong lịch sử.

### 2.3. Xem lịch sử gia hạn

1. Mở hồ sơ xe/tài xế và tìm chứng từ hiện hành.
2. Mở phần **Các bản trước** của chứng từ nếu có.
3. Đối chiếu số chứng từ, ngày cấp, ngày hết hạn và file của từng bản.

Gia hạn tạo một bản kế tiếp trong lịch sử. Khi có giấy tờ mới, dùng **Gia hạn** để giữ đầy đủ chuỗi bản cũ.

Khi sửa loại giấy tờ, hệ thống tính lại hạn trên hồ sơ cho cả loại cũ và loại mới. Khi bỏ hạn hoặc xóa bản cuối cùng có hạn của một loại, hạn tương ứng trên hồ sơ được xóa. Xóa bản gia hạn hiện hành sẽ khôi phục bản trước và tính lại hạn theo bản được khôi phục.

Hướng dẫn kỹ thuật về nơi lưu, sao lưu, chuyển thư mục và kiểm thử: [Lưu trữ và kiểm thử chứng từ](COMPLIANCE_STORAGE_AND_TESTING.md).

## 3. Thêm người dùng thủ công và đồng bộ CMS

### 3.1. Thêm tài khoản chưa có trong CMS

**Mục đích:** tạo người dùng để cấp quyền cho nhân sự chưa được đồng bộ từ CMS.

1. Vào **Gán vai trò tài khoản**.
2. Chọn nút thêm người dùng.
3. Nhập **Họ tên** và **Email**. Đây là hai trường bắt buộc.
4. Nhập mã nhân viên và số điện thoại nếu có.
5. Chọn vai trò ban đầu nếu cần.
6. Lưu và kiểm tra người dùng trong danh sách.
7. Nếu chưa gán vai trò lúc tạo, mở bản ghi tài khoản để gán vai trò phù hợp.

**Kết quả:** tài khoản được tạo với nguồn `manual`; nếu có chọn vai trò, vai trò được gán cùng lúc. Hệ thống ghi nhận thao tác tạo người dùng.

Người dùng đăng nhập bằng **Google với đúng email đã khai báo**. Không có thao tác cấp mật khẩu đăng nhập trên màn hình này. Email ngoài tên miền trường cũng có thể được sử dụng khi đã được thêm trước vào hệ thống.

Nếu báo email đã tồn tại, tìm tài khoản hiện có để gán vai trò. Không tạo thêm bản ghi bằng email khác chỉ để bỏ qua thông báo này.

### 3.2. Điều gì xảy ra khi đồng bộ CMS sau đó?

| Tình huống | Cách hệ thống xử lý |
|---|---|
| CMS có cùng email với tài khoản tạo thủ công | Liên kết tài khoản hiện có sang nguồn `cms`, giữ ID và vai trò; ghi nhận lịch sử liên kết. |
| CMS để trống một trường thông tin | Giữ giá trị hiện có bên điều vận cho trường đó. |
| CMS có cùng mã nhân viên nhưng khác email với tài khoản thủ công | Cảnh báo khả năng trùng; không tự gộp tài khoản. |

Quản trị cần kiểm tra cảnh báo trùng mã nhân viên trước khi phân quyền tiếp. Cách chạy lệnh đồng bộ được trình bày ở [phần 9.3](#93-đồng-bộ-người-dùng-từ-cms).

## 4. Checklist dành cho tài xế

### 4.1. Chọn đúng loại checklist

| Loại | Phạm vi kiểm tra | Mẫu ban đầu |
|---|---|---|
| Daily | An toàn hằng ngày | 30 mục |
| Weekly | Tuân thủ hằng tuần | 16 mục |

Số mục có thể thay đổi khi điều phối cập nhật cấu hình. Tài xế kiểm tra theo nội dung xuất hiện trên phiếu.

Một tài xế có thể làm nhiều phiếu trong ngày, ví dụ cho các xe, ca hoặc chuyến khác nhau. Hệ thống tính kỳ đã làm khi có ít nhất một phiếu **đã nộp** trong ngày/tuần tương ứng; phiếu nháp chưa được tính. Đã làm không đồng nghĩa kết quả đạt.

### 4.2. Bắt đầu phiếu

1. Từ trang chính tài xế, mở thẻ **Checklist hôm nay**, hoặc vào **Checklist xe**.
2. Chọn mẫu Daily hoặc Weekly.
3. Chọn đúng xe cần kiểm tra.
4. Chọn chuyến/ngày đưa đón liên quan nếu có trong danh sách; có thể lập phiếu không gắn chuyến.
5. Nhập địa điểm kiểm tra nếu cần.
6. Bấm nút bắt đầu để tạo phiếu nháp.
7. Kiểm tra biển số và thông tin chuyến trên phiếu trước khi đánh giá.

Hệ thống có thể điền sẵn thông tin xe, tài xế, tuyến, giờ xuất bến hoặc bảo mẫu/giám sát từ dữ liệu liên quan. Kiểm tra lại các thông tin hiển thị trước khi nộp.

### 4.3. Ghi kết quả từng mục

1. Đọc nội dung kiểm tra và yêu cầu/quy định.
2. Kiểm tra thực tế, sau đó chọn kết quả:

| Kết quả | Khi sử dụng |
|---|---|
| **Đạt** (`OK`) | Nội dung đáp ứng yêu cầu. |
| **Không đạt** (`NG`) | Có lỗi hoặc không đáp ứng yêu cầu. |
| **Không áp dụng** (`N/A`) | Nội dung không áp dụng và mẫu cho phép chọn N/A. |

3. Khi chọn **Không đạt**, ghi rõ lỗi và phương án xử lý trong ô ghi chú.
4. Nếu mục yêu cầu ảnh khi không đạt, bấm **Chụp ảnh** và gửi ảnh minh chứng.
5. Kiểm tra ảnh đã tải lên thành công; có thể xóa ảnh gửi nhầm khi phiếu còn nháp.
6. Tiếp tục đến khi tất cả mục đều có kết quả.
7. Bổ sung ghi chú chung và người kiểm tra nếu cần.

Ảnh/file minh chứng được hỗ trợ: PDF, JPG/JPEG, PNG, WebP, HEIC/HEIF, tối đa **15 MB/file**. Chọn ảnh rõ để điều phối có thể kiểm tra.

Các mục giấy tờ có thể hiện gợi ý **còn hạn**, **sắp hết hạn**, **đã hết hạn** hoặc **chưa có giấy tờ**. Đây là thông tin đối chiếu; tài xế vẫn phải kiểm tra và tự chọn kết quả.

**Ví dụ ghi chú NG:** “Lốp sau bên phải bị rách; đã báo điều phối, đề nghị đổi xe trước khi xuất bến.”

### 4.4. Lưu nháp và làm tiếp

Phiếu tự lưu sau khi thay đổi nội dung. Theo dõi thông báo **Đang lưu** / **Đã lưu** ở đầu màn hình.

- Chờ thấy **Đã lưu** trước khi đóng màn hình.
- Muốn làm tiếp, mở lại phiếu nháp trong danh sách checklist.
- Nếu hiện **Chưa lưu**, kiểm tra kết nối và thử lại; không mặc định nội dung đã được lưu.
- Có thể **Hủy phiếu nháp** khi tạo nhầm. Sau khi xác nhận, dữ liệu của phiếu bị xóa.

### 4.5. Nộp và đọc kết luận

1. Kiểm tra không còn mục chưa trả lời.
2. Kiểm tra các mục NG đã có ghi chú và ảnh nếu bắt buộc.
3. Bấm **Nộp checklist**.
4. Nếu hệ thống đánh dấu lỗi, bổ sung nội dung còn thiếu rồi nộp lại.
5. Đọc kết luận và thời gian nộp trên phiếu.

| Kết luận | Cách tính |
|---|---|
| Đủ điều kiện khởi hành | Không có mục Critical không đạt. |
| Không đủ điều kiện khởi hành | Có ít nhất một mục Critical không đạt. |

Mục không nghiêm trọng bị NG vẫn được ghi nhận nhưng không tự làm kết luận thành “Không đủ điều kiện”.

Nếu có Critical NG, hệ thống báo điều phối và hiển thị cảnh báo cho tài xế. **Phần mềm hiện không tự chặn thao tác chuyến.** Tài xế cần làm theo hướng xử lý của điều phối và quy trình an toàn của đơn vị.

Phiếu đã nộp không sửa được. Nếu cần ghi nhận tình trạng sau khi đã khắc phục, lập phiếu mới và ghi rõ việc kiểm tra lại.

### 4.6. Tra cứu và tải phiếu

1. Vào danh sách checklist của mình.
2. Mở phiếu cần xem theo mã, xe và thời gian.
3. Xem kết quả, ghi chú, ảnh, thời gian kiểm tra và ghi chú điều phối nếu có.
4. Chọn **Tải phiếu PDF** khi cần in hoặc lưu hồ sơ.

Mẫu in Daily có thể lấy thông tin giấy tờ từ phiếu Weekly gần nhất trong vòng 14 ngày hoặc từ kho chứng từ. Nếu thấy thông tin giấy tờ chưa đúng, đối chiếu nguồn giấy tờ/phiếu Weekly trước khi sử dụng bản in.

## 5. Theo dõi và cấu hình checklist

### 5.1. Xem tuân thủ theo kỳ

1. Vào **Checklist tài xế** trong ứng dụng quản trị.
2. Mở tab **Tuân thủ**.
3. Chọn loại checklist và khoảng ngày.
4. Bấm **Xem**.
5. Đối chiếu từng tài xế và kỳ kiểm tra trong ma trận.
6. Mở phiếu liên quan để kiểm tra kết quả thực tế.
7. Dùng **Xuất Excel** nếu cần tổng hợp và gửi báo cáo.

Kỳ có phiếu nháp vẫn chưa được tính hoàn thành. Kỳ đã nộp có thể có phiếu không đạt; cần đọc kết luận cùng với trạng thái tuân thủ.

### 5.2. Xem phiếu và ghi nhận xử lý

1. Mở tab **Danh sách phiếu**.
2. Tìm theo mã phiếu, tài xế hoặc biển số; lọc loại, khoảng ngày và kết luận khi cần.
3. Mở phiếu cần xử lý.
4. Bật **Chỉ hiện mục không đạt** nếu muốn tập trung vào các lỗi.
5. Xem ghi chú và ảnh minh chứng từng mục.
6. Nhập **Ghi chú điều phối**, ví dụ “Đã đổi xe, xe lỗi chuyển kỹ thuật kiểm tra”.
7. Bấm **Đánh dấu đã xem**; với phiếu đã xem, dùng **Cập nhật ghi chú** khi cần bổ sung.

Thao tác này lưu người và thời gian đã xem. **Đã xem** không thay đổi kết quả kiểm tra của tài xế và không biến phiếu không đạt thành phiếu đạt.

### 5.3. Cấu hình mẫu checklist

1. Mở **Cấu hình** từ khu vực checklist.
2. Chọn mẫu Daily hoặc Weekly cần điều chỉnh.
3. Bật/tắt mẫu theo nhu cầu sử dụng.
4. Với từng mục, điều chỉnh nhóm, nội dung kiểm tra, yêu cầu, mức độ và hành động xử lý.
5. Thiết lập **Cho chọn N/A**, **Bắt buộc ảnh khi NG** và **Bật** mục.
6. Bấm **Lưu** cho mục đã sửa.
7. Dùng **Thêm mục kiểm tra** để thêm nội dung; dùng mũi tên lên/xuống để đổi thứ tự.
8. Chọn **Xóa** và xác nhận khi cần bỏ một mục.

Các thay đổi dùng cho phiếu tạo sau đó. Phiếu đã tạo giữ nội dung mẫu tại thời điểm tạo, kể cả khi mẫu được sửa hoặc mục bị xóa.

### 5.4. Cấu hình nhắc nhở

1. Trong cấu hình mẫu, tìm phần **Khung giờ nhắc**.
2. Chọn **Thêm khung giờ**.
3. Nhập giờ nhắc và chọn các ngày trong tuần.
4. Nếu cần tổng hợp người chưa làm cho điều phối, nhập số phút tại **Báo điều phối sau**.
5. Bật khung giờ và bấm **Lưu**.

**Ví dụ:** Daily nhắc 06:00 từ thứ Hai đến thứ Sáu, báo điều phối sau 30 phút. Tài xế chưa nộp phiếu trong ngày sẽ nhận nhắc; sau thời gian cấu hình, điều phối nhận tổng hợp những người còn chưa hoàn thành.

Hệ thống kiểm tra lịch mỗi 5 phút theo giờ Việt Nam, nên thông báo có thể đến ở lần kiểm tra tiếp theo. Mỗi khung giờ chỉ nhắc một lần/ngày cho cùng tài xế. Tính năng cần lịch chạy tự động trên máy chủ hoạt động.

## 6. Lập và theo dõi hồ sơ chi phí

### 6.1. Chọn loại hồ sơ

| Loại | Mục đích | Tiền tố mã | Ghi nhận chi phí thực tế từ dòng hồ sơ |
|---|---|---|---|
| Phiếu đề xuất | Đề nghị mua hàng hoặc sử dụng dịch vụ | `DX` | Không; đây là dự kiến. |
| Đề nghị thanh toán | Thanh toán khoản đã chi | `TT` | Có. |
| Đề nghị tạm ứng | Đề nghị ứng tiền trước khi chi | `TU` | Không; đây là dự kiến. |
| Đề nghị hoàn ứng | Quyết toán sau tạm ứng | `HU` | Có. |
| Bảng kê không hóa đơn | Kê khai các khoản mua không có hóa đơn | `BK` | Có. |

Mã hồ sơ được cấp tự động, ví dụ `TT-261008-1`. Số thứ tự đếm riêng theo tiền tố và bắt đầu lại mỗi ngày.

### 6.2. Tạo hồ sơ mới

1. Vào **Hồ sơ chi phí** trong ứng dụng tài xế. Có thể mở từ lối vào hồ sơ trên trang chi phí.
2. Chọn loại hồ sơ cần lập.
3. Chọn **Căn cứ theo hồ sơ** nếu lập tiếp từ đề xuất hoặc tạm ứng trước đó.
4. Kiểm tra phần **Người đề nghị**: họ tên, số điện thoại, email và đơn vị/bộ phận.
5. Chọn xe và nhập ngày lập.
6. Nhập nội dung phù hợp với loại hồ sơ:

| Loại | Nội dung cần chú ý |
|---|---|
| Đề xuất | Mục đích sử dụng; căn cứ; ngày cần nhận hàng/dịch vụ; thông tin gấp và lý do nếu có; người nhận bàn giao/thu mua khi cần. |
| Thanh toán/tạm ứng/hoàn ứng | Nội dung thanh toán; thời gian thanh toán; hình thức tiền mặt hoặc chuyển khoản; tài khoản nhận nếu chuyển khoản. |
| Bảng kê | Diễn giải khoản mua; ngày mua; thông tin người bán cho từng dòng. |

7. Nhập các dòng chi tiết theo phần tiếp theo.
8. Kiểm tra chức danh và tên **Người ký**; chỉnh lại nếu người ký thực tế khác thông tin mặc định.
9. Bấm **Lưu hồ sơ**.
10. Kiểm tra mã hồ sơ và trạng thái **Nháp**.

Lưu hồ sơ chưa phải nộp hồ sơ. Khi còn nháp, bạn có thể mở lại để sửa.

### 6.3. Nhập các khoản chi

**Cách A — lấy khoản đã khai:**

1. Với loại hồ sơ ghi nhận chi phí thực tế, chọn **Chi phí đã khai**.
2. Chọn các khoản cần đưa vào hồ sơ.
3. Bấm **Thêm … khoản**.
4. Kiểm tra lại số tiền, xe và thông tin từng dòng.

Các dòng liên kết dùng lại khoản chi có sẵn. Một số thông tin sẽ không sửa trực tiếp trong trình soạn hồ sơ; kiểm tra khoản gốc trước khi nộp.

**Cách B — nhập khoản mới:**

1. Bấm **Thêm dòng**.
2. Nhập tên mặt hàng/diễn giải, loại chi phí và xe nếu áp dụng.
3. Nhập đơn vị tính, số lượng và đơn giá.
4. Kiểm tra thành tiền hiển thị và tổng hồ sơ.
5. Nhập ngày mua, tình trạng hóa đơn và thông tin người bán khi cần.
6. Thêm ghi chú nếu có.

**Ví dụ:** vật tư/phụ tùng, đơn vị “cái”, số lượng 2, đơn giá 150.000 đồng → thành tiền 300.000 đồng.

Với TT/HU/BK, dòng mới có thể tạo khoản chi phí thực tế liên kết vào sổ xe. Với DX/TU, dòng chỉ là khoản dự kiến, chưa tạo khoản chi thực tế.

### 6.4. Khoản có và không có hóa đơn

- Có hóa đơn: đánh dấu **Có hóa đơn**; khai thông tin hóa đơn tại nơi hỗ trợ nhập thông tin khoản chi.
- Không có hóa đơn: bỏ đánh dấu, nhập tên người bán, địa chỉ người bán và ngày mua để hoàn thiện bảng kê.

Khi nộp TT/HU có dòng không hóa đơn, hệ thống tự tạo/cập nhật **Bảng kê không hóa đơn** liên kết với hồ sơ cha. Bạn không cần nhập lại cùng khoản chỉ để tạo bảng kê con.

Bảng kê cần tên người bán và ngày mua cho từng dòng. Hãy bổ sung trước khi nộp; không bỏ qua thông tin người bán chỉ vì khoản chi nhỏ.

**Ví dụ:** hồ sơ thanh toán gồm nhiên liệu 800.000 đồng có hóa đơn và ăn uống 120.000 đồng không hóa đơn. Tổng hồ sơ là 920.000 đồng; bảng kê con chỉ chứa khoản 120.000 đồng. Bảng kê dùng chung khoản chi với hồ sơ cha, không cộng thêm một khoản chi mới vào sổ.

### 6.5. Kiểm tra và nộp hồ sơ

1. Mở hồ sơ nháp đã lưu.
2. Kiểm tra họ tên người đề nghị, nội dung, xe, các khoản chi và người ký.
3. Đảm bảo có ít nhất một dòng chi phí, mỗi dòng và tổng hồ sơ đều lớn hơn 0.
4. Với đề xuất, nhập mục đích sử dụng.
5. Với đề nghị thanh toán/tạm ứng/hoàn ứng, chọn hình thức thanh toán; nếu chuyển khoản, nhập số tài khoản nhận.
6. Kiểm tra thông tin bảng kê không hóa đơn nếu có.
7. Bấm **Nộp hồ sơ** và xác nhận.
8. Kiểm tra trạng thái **Đã nộp**.

Sau khi nộp, hồ sơ và các khoản chi liên quan bị khóa sửa/xóa. Muốn sửa, cần kế toán trả lại hồ sơ.

### 6.6. In, trình ký và gửi bản scan

1. Mở hồ sơ đã nộp.
2. Bấm **PDF** hoặc **Excel** để tải biểu mẫu.
3. Đối chiếu số tiền, số tiền bằng chữ, thông tin người đề nghị và vị trí người ký.
4. In và trình ký theo quy trình của đơn vị.
5. Scan/chụp rõ bản có chữ ký.
6. Chọn **Upload bản scan có chữ ký** và tải file lên.
7. Kiểm tra file trong phần bản scan và trạng thái **Đã ký**.

Bản scan hỗ trợ PDF và các định dạng ảnh JPG/JPEG, PNG, WebP, HEIC/HEIF, tối đa **15 MB/file**. Việc ghi tên người ký khi lập hồ sơ chưa phải ký hồ sơ; trạng thái đã ký được ghi nhận khi tải bản scan sau khi nộp.

### 6.7. Lập hồ sơ tiếp nối

- Từ phiếu đề xuất, dùng **Lập Đề nghị thanh toán từ phiếu này** hoặc **Lập Đề nghị tạm ứng từ phiếu này** khi nút xuất hiện.
- Từ hồ sơ tạm ứng, dùng **Lập Đề nghị hoàn ứng**.
- Trong trình soạn, có thể chọn hồ sơ phù hợp tại **Căn cứ theo hồ sơ**.

Hệ thống có thể điền sẵn nội dung từ hồ sơ trước và giữ liên kết căn cứ. Kiểm tra lại số lượng, đơn giá, khoản chi thực tế và hình thức thanh toán; số dự kiến không nhất thiết bằng số đã chi. Không dùng hồ sơ đã hủy làm căn cứ.

### 6.8. Theo dõi trạng thái và sửa hồ sơ bị trả lại

| Trạng thái | Việc tiếp theo |
|---|---|
| Nháp | Hoàn thiện và nộp; có thể sửa hoặc hủy. |
| Đã nộp | Tải mẫu in, trình ký và gửi bản scan; muốn sửa phải được trả lại. |
| Đã ký | Chờ kế toán xử lý thanh toán. |
| Bị trả lại | Đọc lý do, sửa thông tin, lưu và nộp lại. |
| Đã thanh toán | Tra cứu hoặc tải file; dữ liệu đã khóa. |
| Đã hủy | Hồ sơ không còn tiếp tục xử lý. |

Để sửa hồ sơ bị trả lại:

1. Mở hồ sơ có trạng thái **Bị trả lại**.
2. Đọc **Lý do trả lại**.
3. Bấm **Sửa**.
4. Điều chỉnh nội dung theo lý do.
5. Bấm **Lưu hồ sơ**, kiểm tra lại rồi **Nộp hồ sơ**.

### 6.9. Hủy hồ sơ

1. Mở hồ sơ và chọn **Hủy hồ sơ** nếu nút đang có.
2. Đọc thông báo về các khoản chi liên quan.
3. Xác nhận hủy và kiểm tra trạng thái **Đã hủy**.

Hủy được khi hồ sơ còn nháp, đã nộp hoặc bị trả lại. Hồ sơ đã ký/đã thanh toán không nằm trong các trạng thái được hủy bằng thao tác này.

Các khoản do chính hồ sơ tạo có thể bị xóa khi hủy nếu chưa được xác nhận và không đang được hồ sơ khác sử dụng. Các khoản đã khai trước rồi chọn đưa vào hồ sơ không bị coi là khoản do hồ sơ tạo.

## 7. Xử lý hồ sơ dành cho kế toán

### 7.1. Tiếp nhận và kiểm tra

1. Vào **Hồ sơ chi phí xe** trong ứng dụng quản trị.
2. Tìm theo mã, nội dung hoặc người lập; lọc loại hồ sơ và khoảng ngày khi cần.
3. Mở hồ sơ cần xử lý.
4. Kiểm tra người đề nghị, xe, ngày lập, căn cứ, hình thức thanh toán và từng khoản chi.
5. Đối chiếu hồ sơ cha/con, đặc biệt bảng kê không hóa đơn.
6. Tải PDF/Excel và xem bản scan đã ký nếu đã được gửi.

Điều phối có quyền xem có thể tra cứu hồ sơ, nhưng cần quyền quản lý chi phí để trả lại hoặc xác nhận thanh toán.

### 7.2. Trả lại để bổ sung

1. Mở hồ sơ đang **Đã nộp** hoặc **Đã ký**.
2. Chọn **Trả lại**.
3. Nhập lý do cụ thể, ví dụ “Bổ sung ngày mua và tên người bán cho khoản ăn uống”.
4. Bấm **Xác nhận trả lại**.
5. Kiểm tra trạng thái **Bị trả lại**.

Người lập sẽ thấy lý do và có thể sửa, nộp lại. Nếu khoản chi còn được một hồ sơ khác đang khóa sử dụng, việc trả lại một hồ sơ không nhất thiết mở khóa toàn bộ khoản chi đó.

### 7.3. Gửi bản scan thay người lập

1. Mở hồ sơ đã nộp hoặc đã ký.
2. Chọn **Upload bản scan có chữ ký** nếu có quyền.
3. Tải bản đã ký và kiểm tra file lưu thành công.

Người lập và nhân viên có quyền quản lý đều có thể gửi bản scan theo điều kiện trạng thái.

### 7.4. Xác nhận đã thanh toán

1. Mở hồ sơ cần xác nhận sau khi đã xử lý thanh toán thực tế.
2. Đối chiếu số tiền và chứng từ.
3. Bấm **Đã thanh toán**.
4. Xác nhận và kiểm tra trạng thái, thời gian thanh toán.

Hệ thống khóa hồ sơ, xác nhận các khoản chi còn chờ và chuyển bảng kê con liên quan sang đã thanh toán.

**Giới hạn hiện tại:** nút xác nhận thanh toán được phép ở cả trạng thái **Đã nộp** và **Đã ký**; phần mềm không bắt buộc có bản scan trước thao tác này. Kế toán cần tuân thủ yêu cầu chứng từ của đơn vị. Thao tác này ghi nhận trạng thái trên phần mềm, không tự chuyển tiền ngân hàng.

## 8. Sổ chi phí theo xe và cấu hình mẫu in

### 8.1. Xem sổ chi phí

1. Vào **Sổ chi phí theo xe**.
2. Chọn **Từ ngày** và **Đến ngày**.
3. Xem tổng hợp từng xe: số khoản, tổng tiền và các chỉ số hiển thị.
4. Chọn xe để xem từng khoản và hồ sơ thanh toán liên quan.
5. Đối chiếu tổng hợp theo loại và theo tháng.
6. Chọn xuất Excel khi cần đối soát.

Sổ tổng hợp các khoản chi gắn với xe từ chuyến, khai báo riêng hoặc hồ sơ chi phí. Khoản bị từ chối không được đưa vào tổng hợp. Khoảng ngày dựa trên ngày ghi nhận của khoản chi; nếu chưa có ngày ghi nhận, hệ thống dùng ngày tạo.

Tổng chi phí có thể gồm khoản chưa xác nhận. Vì vậy, **tổng chi phí không đồng nghĩa tổng đã thanh toán**. Đề xuất và tạm ứng chưa tạo khoản chi thực tế nên không tự xuất hiện như chi phí phát sinh từ dòng dự kiến.

### 8.2. Cấu hình thông tin mẫu in

1. Vào **Cấu hình mẫu in**.
2. Nhập thông tin doanh nghiệp: tên, mã số thuế, địa chỉ, nơi tổ chức thu mua và địa danh trên dòng ngày tháng.
3. Nhập đơn vị mặc định của người lập.
4. Cập nhật mã biểu mẫu, ngày ban hành, lần ban hành cho các loại mẫu.
5. Nhập chức danh và tên người ký mặc định.
6. Kiểm tra thứ tự người ký khớp với vị trí trên mẫu in.
7. Bấm **Lưu**.

| Mẫu | Thứ tự vị trí ký theo cấu hình ban đầu |
|---|---|
| Đề xuất | Giám đốc vận hành → Trưởng đơn vị → Tổ trưởng tài xế → Phó tổng giám đốc. |
| Thanh toán/tạm ứng/hoàn ứng | Trưởng đơn vị → Phòng kế toán → Phó tổng giám đốc. |
| Bảng kê không hóa đơn | Phó tổng giám đốc. |

Thông tin mặc định giúp điền hồ sơ mới; người lập vẫn có thể sửa người ký trên hồ sơ. Hồ sơ lưu bản chụp thông tin biểu mẫu, nên thay cấu hình không tự viết lại thông tin của hồ sơ đã tạo.

## 9. Công cụ dành cho người vận hành hệ thống

Các thao tác ở phần này chạy trong terminal tại thư mục dự án và dùng cơ sở dữ liệu đã cấu hình trên máy đang chạy. Đây không phải thao tác trên màn hình dành cho tài xế/điều phối.

### 9.1. Import xe và tài xế từ Excel bàn giao

**Chuẩn bị:** file `.xlsx` theo cấu trúc bàn giao; mặc định có hai sheet **TT xe nội bộ** và **TT Tài xế**. Các tài khoản cần liên kết nên tồn tại trước khi import.

**Bước 1 — chạy thử:**

```bash
php artisan fleet:import-handover "data/ban-giao.xlsx" --dry-run
```

Thay đường dẫn mẫu bằng file thực tế. Lệnh chạy thử xử lý rồi rollback, không lưu thay đổi vào cơ sở dữ liệu.

**Bước 2 — kiểm tra báo cáo:**

- Xem dòng được tạo/cập nhật cho tài xế và xe.
- Kiểm tra tài khoản đã khớp, biển số, số chỗ và tài xế phụ trách.
- Kiểm tra cảnh báo và xử lý dữ liệu chưa khớp.
- Hệ thống nhận diện xe theo biển số, tài xế theo CCCD; không hiểu đây là thao tác luôn tạo mới.

Ưu tiên khớp tài khoản: liên kết chỉ định bằng `--link`, mã nhân viên, họ tên, sau đó số điện thoại.

**Bước 3 — chỉ định liên kết nếu cần:**

```bash
php artisan fleet:import-handover "data/ban-giao.xlsx" --dry-run --link="NV001=taixe@example.com"
```

Có thể lặp lại `--link` cho nhiều tài xế. Thay mã nhân viên và email bằng thông tin thực tế.

**Bước 4 — import thật sau khi đã đối chiếu:**

```bash
php artisan fleet:import-handover "data/ban-giao.xlsx" --link="NV001=taixe@example.com"
```

Không có `--dry-run` thì lệnh ghi dữ liệu. Nếu tên sheet khác, chỉ định:

```bash
php artisan fleet:import-handover "data/ban-giao.xlsx" --driver-sheet="Danh sach tai xe" --vehicle-sheet="Danh sach xe" --dry-run
```

**Bước 5 — kiểm tra trên ứng dụng:** xem hồ sơ xe/tài xế, thông tin giấy tờ, tài khoản liên kết và vai trò tài xế. Import có thể lưu thông tin GPLX, CCCD và liên kết giấy tờ từ file; kiểm tra lại file/liên kết thực tế trước khi sử dụng.

### 9.2. Dọn dữ liệu test

**Phạm vi:** xóa dữ liệu nghiệp vụ, xe/tài xế, học sinh đưa đón, nhật ký/thông báo và bảng giá thuộc các nhóm lệnh quản lý; gỡ vai trò tài xế, giữ tài khoản và cấu hình hệ thống.

> Lệnh không lọc từng bản ghi theo nhãn “test”. Nó xóa dữ liệu trong các nhóm bảng đã định nghĩa. Chỉ thực hiện khi đã xác định đúng cơ sở dữ liệu cần dọn và có bản sao lưu để khôi phục.

1. Chạy xem trước:

```bash
php artisan data:purge-test
```

2. Kiểm tra tên database, các bảng, số dòng sẽ xóa, tài khoản sẽ bị gỡ vai trò tài xế và bảng chưa được phân loại.
3. Khi đã sẵn sàng xóa thật, chạy:

```bash
php artisan data:purge-test --force
```

4. Đọc và trả lời xác nhận của lệnh trong terminal tương tác.
5. Kiểm tra kết quả số dòng, vai trò và file đã xóa.

Nếu cần giữ file trên ổ đĩa:

```bash
php artisan data:purge-test --force --keep-files
```

`--keep-files` chỉ giữ file vật lý; không giữ các bản ghi dữ liệu bị lệnh xóa. Các bảng chưa phân loại được báo riêng và không bị xóa. Sau khi dọn, cần nhập lại dữ liệu xe/tài xế thực tế và kiểm tra vai trò trước khi sử dụng.

### 9.3. Đồng bộ người dùng từ CMS

1. Đảm bảo kết nối CMS đã được cấu hình trên máy chủ.
2. Chạy xem trước:

```bash
php artisan cms:sync-users --dry-run
```

3. Kiểm tra các tài khoản dự kiến đồng bộ, liên kết tài khoản thủ công và cảnh báo trùng mã nhân viên.
4. Khi dữ liệu phù hợp, chạy:

```bash
php artisan cms:sync-users
```

5. Kiểm tra báo cáo cuối lệnh và danh sách tài khoản trên ứng dụng. Với tài khoản thủ công được nối theo email, kiểm tra nguồn đã thành `cms` và vai trò vẫn còn.

## 10. Các tình huống thường gặp

| Hiện tượng | Cách xử lý |
|---|---|
| Không thấy menu checklist/hồ sơ chi phí | Kiểm tra phiên bản đã triển khai, công tắc tính năng và quyền tài khoản. |
| Kế toán có quyền chi phí nhưng không vào được quản trị | Nhờ quản trị kiểm tra vai trò truy cập ứng dụng; vai trò `accountant` đơn lẻ chưa đủ ở phiên bản này. |
| Gia hạn không lưu được | Kiểm tra file mới, dung lượng/định dạng, ngày hết hạn mới sau ngày cũ và ngày cấp không sau ngày hết hạn; chọn bản hiện hành. |
| Không tìm thấy bản chứng từ cũ trong kho | Đổi bộ lọc trạng thái hoặc xem **Các bản trước** trong hồ sơ xe/tài xế. |
| Email người dùng đã tồn tại | Tìm tài khoản hiện có để gán vai trò. |
| Checklist còn thiếu mục | Hoàn thành mọi mục; bổ sung ghi chú NG và ảnh bắt buộc. N/A chỉ có ở mục được cho phép. |
| Checklist đã làm nhưng vẫn bị tính chưa hoàn thành | Kiểm tra phiếu đã **nộp**, đúng loại Daily/Weekly và đúng kỳ; lưu nháp chưa đủ. |
| Checklist có kết luận không đủ điều kiện | Xem các mục Critical NG, báo/trao đổi điều phối; sau khắc phục, lập phiếu kiểm tra lại khi cần. |
| Không nhận nhắc checklist | Kiểm tra mẫu, khung giờ, ngày trong tuần, trạng thái đã nộp và lịch chạy tự động trên máy chủ. |
| Không thấy khoản chi trong bộ chọn | Khoản có thể không còn đủ điều kiện hoặc đã thuộc hồ sơ cùng loại còn hiệu lực; đối chiếu khoản gốc và hồ sơ liên quan. |
| Không sửa/xóa được khoản chi | Kiểm tra khoản đã xác nhận, chuyến đã khóa tài chính hoặc hồ sơ liên kết đã nộp/đã ký/đã thanh toán. |
| Hồ sơ bị trả lại | Đọc lý do, sửa, lưu rồi nộp lại. |
| Không có nút hủy | Hồ sơ có thể đã ký/đã thanh toán hoặc tài khoản không có quyền tương ứng. |
| Hồ sơ không nộp được | Kiểm tra người đề nghị, dòng chi phí và số tiền; mục đích đề xuất; hình thức thanh toán/tài khoản nhận; thông tin bảng kê. |
| Số trong sổ xe khác tổng đã thanh toán | Sổ có thể bao gồm khoản chưa xác nhận; đối chiếu trạng thái khoản chi và hồ sơ. |
| Nội dung thay đổi ở nơi khác | Tải lại phiếu/hồ sơ, kiểm tra bản mới nhất rồi thao tác tiếp; tránh chỉnh cùng hồ sơ ở nhiều tab. |
| Import cảnh báo không khớp tài khoản | Kiểm tra tài khoản, mã nhân viên, họ tên, số điện thoại; dùng `--link` và chạy thử lại. |

