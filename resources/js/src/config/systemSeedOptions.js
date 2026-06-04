/**
 * Presets aligned with database seeders (RbacSeeder, FeatureToggleSeeder).
 * Update these when PHP seed definitions change.
 */

export const SEED_ROLE_PRESETS = [
    { name: 'superadmin', display_name: 'Super Admin' },
    { name: 'admin', display_name: 'Admin' },
    { name: 'dispatcher', display_name: 'Dispatcher' },
    { name: 'driver', display_name: 'Tài xế' },
    { name: 'accountant', display_name: 'Kế toán' },
    { name: 'internal_user', display_name: 'User nội bộ' },
]

/** Same order as RbacSeeder $permissions */
export const SEED_PERMISSION_PRESETS = [
    { name: 'request.create', display_name: 'request.create' },
    { name: 'request.update_own', display_name: 'request.update_own' },
    { name: 'request.cancel_own', display_name: 'request.cancel_own' },
    { name: 'request.approve', display_name: 'request.approve' },
    { name: 'request.paper.manage', display_name: 'request.paper.manage' },
    { name: 'trip.assign', display_name: 'trip.assign' },
    { name: 'trip.view_all', display_name: 'trip.view_all' },
    { name: 'trip.view_own', display_name: 'trip.view_own' },
    { name: 'trip.update_status', display_name: 'trip.update_status' },
    { name: 'trip.record.create', display_name: 'trip.record.create' },
    { name: 'trip.event.create', display_name: 'trip.event.create' },
    { name: 'trip.cost.view', display_name: 'trip.cost.view' },
    { name: 'trip.cost.reconcile', display_name: 'trip.cost.reconcile' },
    { name: 'payment.reconcile', display_name: 'payment.reconcile' },
    { name: 'payment.execute', display_name: 'payment.execute' },
    { name: 'cargo.manage', display_name: 'cargo.manage' },
    { name: 'route.manage', display_name: 'route.manage' },
    { name: 'student.manage', display_name: 'student.manage' },
    { name: 'attachment.upload', display_name: 'attachment.upload' },
    { name: 'report.view', display_name: 'report.view' },
    { name: 'report.export', display_name: 'report.export' },
    { name: 'resource.driver.manage', display_name: 'resource.driver.manage' },
    { name: 'resource.vehicle.manage', display_name: 'resource.vehicle.manage' },
    { name: 'resource.provider.manage', display_name: 'resource.provider.manage' },
    { name: 'user.manage', display_name: 'user.manage' },
    { name: 'audit_log.view', display_name: 'audit_log.view' },
    { name: 'data.override_confirmed', display_name: 'data.override_confirmed' },
    { name: 'system.roles.manage', display_name: 'system.roles.manage' },
    { name: 'system.permissions.manage', display_name: 'system.permissions.manage' },
    { name: 'system.user_roles.manage', display_name: 'system.user_roles.manage' },
    { name: 'system.feature_toggles.manage', display_name: 'system.feature_toggles.manage' },
    { name: 'dispatch.settings.manage', display_name: 'dispatch.settings.manage' },
]

export const SEED_FEATURE_TOGGLE_PRESETS = [
    { key: 'module.overview', name: 'Tổng quan & lịch', module: 'overview' },
    { key: 'module.operations', name: 'Điều vận (yêu cầu, chuyến, chi phí…)', module: 'operations' },
    { key: 'module.d2d_routes', name: 'Tuyến D2D (/routes)', module: 'operations' },
    { key: 'module.finance', name: 'Kế toán / đối soát', module: 'finance' },
    { key: 'module.reports', name: 'Báo cáo', module: 'reports' },
    { key: 'module.pricing', name: 'Bảng giá tham chiếu', module: 'reports' },
    { key: 'module.system.roles', name: 'Quyền hạn — Quản lý Role', module: 'system' },
    { key: 'module.system.permissions', name: 'Quyền hạn — Quản lý Permission', module: 'system' },
    { key: 'module.system.user_roles', name: 'Quyền hạn — Gán quyền người dùng', module: 'system' },
    { key: 'module.system.feature_toggles', name: 'Quyền hạn — Feature toggle', module: 'system' },
    { key: 'module.system.dispatch_settings', name: 'Quyền hạn — Ngưỡng Gấp', module: 'system' },
    { key: 'module.system.audit', name: 'Quyền hạn — Activity log', module: 'system' },
]

/** Common audit `event` values used in app code / middleware */
export const AUDIT_EVENT_PRESETS = [
    { value: 'api.request', label: 'api.request (middleware HTTP)' },
    { value: 'request.create', label: 'request.create' },
    { value: 'request.paper_received', label: 'request.paper_received' },
    { value: 'request.reject', label: 'request.reject' },
    { value: 'request.approve', label: 'request.approve' },
    { value: 'attachment.upload', label: 'attachment.upload' },
    { value: 'attachment.ocr_stub', label: 'attachment.ocr_stub' },
    { value: 'cargo.sla_breached', label: 'cargo.sla_breached' },
]
