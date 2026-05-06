<?php

namespace App\Support;

final class Messages
{
    public const REQUEST_MUST_BE_2H_AHEAD = 'Yêu cầu phải được tạo trước giờ xuất phát ít nhất 2 tiếng (trừ lệnh gấp).';

    public const REQUEST_NOT_PENDING = 'Yêu cầu không ở trạng thái chờ duyệt.';

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
}
