<?php

namespace App\Support;

final class Messages
{
    public const REQUEST_MUST_BE_2H_AHEAD = 'Yêu cầu phải được tạo trước giờ xuất phát ít nhất 2 tiếng (trừ lệnh gấp).';

    public const REQUEST_NOT_PENDING = 'Yêu cầu không ở trạng thái chờ duyệt.';

    public const REQUEST_FILL_PRICE_NOT_APPLICABLE = 'Loại yêu cầu này không áp dụng bước điền giá (door-to-door).';

    public const REQUEST_INVALID_DEPT_HEAD = 'Người được chỉ định không phải trưởng đơn vị đang hoạt động trong hệ thống.';

    public const REQUEST_DEPT_HEAD_REQUIRED = 'Vui lòng chọn Trưởng đơn vị nhận duyệt trước khi chuyển phiếu.';

    public const REQUEST_NOT_PRICE_FILLED = 'Yêu cầu chưa ở trạng thái đã điền giá; không thể duyệt theo quy trình này.';

    public const REQUEST_PAPER_NOT_RECEIVED = 'Phiếu giấy chưa ở trạng thái đã nhận; không thể hoàn tác.';

    public const RESOURCE_VEHICLE_OVERLAP = 'Xe bị trùng lịch trong khung giờ này.';

    public const RESOURCE_DRIVER_OVERLAP = 'Tài xế bị trùng lịch trong khung giờ này.';

    public const OPTIMISTIC_LOCK_CONFLICT = 'Dữ liệu đã thay đổi, vui lòng tải lại (optimistic lock).';

    public const COST_NOT_ACTIONABLE = 'Chi phí không ở trạng thái có thể xử lý.';

    public const RECONCILE_PERIOD_NOT_DRAFT = 'Kỳ đối soát không ở trạng thái draft.';

    public const RECONCILE_PERIOD_NOT_LOCKED = 'Chỉ tạo payment khi kỳ đối soát đã locked.';

    public const ROUTE_VERSION_MUST_BE_APPROVED = 'Chỉ generate trip từ route version đã approved.';

    public const TRIP_FINANCIALLY_LOCKED = 'Chuyến đã thanh toán; không thể thay đổi phân công, chi phí hoặc chứng từ (trừ Admin override chi phí).';

    public const COST_CONFIRMED_NO_ATTACHMENT = 'Chi phí đã xác nhận; không thể đính kèm thêm. Dùng Admin override nếu cần điều chỉnh.';

    public const TRIP_PAID_CANNOT_ADD_TO_RECONCILE = 'Một hoặc nhiều chuyến đã thanh toán; không thể tạo/ghi lại payment trong kỳ đối soát.';

    public const TRIP_TERMINAL_NO_LIST_OR_COST_EDITS = 'Chuyến đã hoàn thành hoặc đã hủy; không thể thêm, sửa hay xóa hành khách hoặc chi phí.';

    public const REQUEST_NOT_RECURRING_INSTANCE = 'Chỉ áp dụng cho phiếu sinh theo định kỳ (đã liên kết template).';

    public const REQUEST_RECURRING_PASSENGER_COUNT_LOCKED = 'Chỉ cập nhật số hành khách trước giờ khởi hành ít nhất 24 giờ.';

    public const REQUEST_RECURRING_STUDENT_COUNT_NOT_SAVED = 'Vui lòng lưu số học sinh thực tế trước khi gửi điều vận.';

    public const REQUEST_RECURRING_STUDENT_COUNT_ALREADY_SUBMITTED = 'Số học sinh đã được gửi chốt cho chuyến này.';

    public const REQUEST_RECURRING_STUDENT_COUNT_SUBMIT_LOCKED = 'Chỉ gửi chốt số học sinh trước giờ khởi hành ít nhất 24 giờ.';

    public const REQUEST_RECURRING_TEMPLATE_POINT_TO_POINT_ONLY = 'Đề xuất định kỳ chỉ áp dụng cho chuyến điểm–điểm (P2P).';

    public const REQUEST_RECURRING_TEMPLATE_EXTRACURRICULAR_ONLY = 'Đề xuất định kỳ chỉ áp dụng cho mục đích hoạt động ngoại khóa (CLB).';

    public const REQUEST_CLONE_INVALID_STATUS = 'Chỉ có thể đặt lại phiếu đã duyệt hoặc đã từ chối.';

    public const TRIP_ALREADY_EXISTS_FOR_REQUEST = 'Yêu cầu đã có chuyến điều vận; không thể duyệt tạo thêm.';

    public const TRIP_ASSIGN_BLOCKED_OPERATIONAL = 'Chuyến đã vận hành hoặc đã kết thúc; không thể phân công lại.';

    public const REQUEST_PDF_REQUIRES_APPROVAL = 'PDF chỉ khả dụng sau khi phiếu đã được duyệt.';

}
