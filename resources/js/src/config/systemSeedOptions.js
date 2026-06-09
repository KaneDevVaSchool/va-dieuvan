/**
 * Presets aligned with database seeders (RbacSeeder, FeatureToggleSeeder).
 * Update these when PHP seed definitions change.
 */

import { getCapabilityLabelForPerm } from './businessCapabilities.js'

function permPreset(name) {
  return { name, display_name: getCapabilityLabelForPerm(name) ?? name }
}

export const SEED_ROLE_PRESETS = [
    { name: 'superadmin', display_name: 'Super Admin' },
    { name: 'admin', display_name: 'Admin' },
    { name: 'dispatcher', display_name: 'Dispatcher' },
    { name: 'driver', display_name: 'Tài xế' },
    { name: 'accountant', display_name: 'Kế toán' },
    { name: 'internal_user', display_name: 'User nội bộ' },
    { name: 'department_head', display_name: 'Trưởng đơn vị' },
]

/** Same order as RbacSeeder $permissions */
export const SEED_PERMISSION_PRESETS = [
    permPreset('request.create'),
    permPreset('request.update_own'),
    permPreset('request.cancel_own'),
    permPreset('request.approve'),
    permPreset('request.fill_price'),
    permPreset('request.approve_dept'),
    permPreset('request.paper.manage'),
    permPreset('trip.assign'),
    permPreset('trip.view_all'),
    permPreset('trip.view_own'),
    permPreset('trip.update_status'),
    permPreset('trip.record.create'),
    permPreset('trip.event.create'),
    permPreset('trip.cost.view'),
    permPreset('trip.cost.reconcile'),
    permPreset('payment.reconcile'),
    permPreset('payment.execute'),
    permPreset('report.view'),
    permPreset('report.export'),
    permPreset('cargo.manage'),
    permPreset('attachment.upload'),
    permPreset('reference_pricing.manage'),
    permPreset('resource.driver.manage'),
    permPreset('resource.vehicle.manage'),
    permPreset('resource.provider.manage'),
    permPreset('user.manage'),
    permPreset('audit_log.view'),
    permPreset('data.override_confirmed'),
    permPreset('system.roles.manage'),
    permPreset('system.permissions.manage'),
    permPreset('system.user_roles.manage'),
    permPreset('system.feature_toggles.manage'),
    permPreset('dispatch.settings.manage'),
    permPreset('tp_program.view'),
    permPreset('tp_program.manage'),
    permPreset('tp_student.view'),
    permPreset('tp_student.manage'),
    permPreset('tp_enrollment.manage'),
    permPreset('tp_attendance.manage'),
    permPreset('tp_attendance.confirm'),
    permPreset('tp_driver_assign.manage'),
    permPreset('tp_cost.manage'),
    permPreset('tp_import.manage'),
    permPreset('tp_report.view'),
    permPreset('tp_audit.view'),
    permPreset('tp_execution.force_complete'),
]

export const SEED_FEATURE_TOGGLE_PRESETS = [
    { key: 'module.overview', name: 'Tổng quan & lịch', module: 'overview' },
    { key: 'module.operations', name: 'Điều vận (yêu cầu, chuyến, chi phí…)', module: 'operations' },
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
