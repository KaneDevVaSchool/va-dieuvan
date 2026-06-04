/**
 * JOB_ROLE_TEMPLATES
 *
 * Predefined role templates for common job functions.
 * Shown as visual cards in the role editor so business users can set up
 * a new role in under 3 minutes without knowing any permission codes.
 *
 * Each template maps to a set of permission names (from SEED_PERMISSION_PRESETS).
 * The actual permission IDs are resolved at runtime by matching against the
 * permissions list returned by the API.
 */

export const JOB_ROLE_TEMPLATES = Object.freeze([
  {
    id: 'dispatcher',
    icon: '🚌',
    title: 'Điều phối vận hành',
    description: 'Xử lý yêu cầu điều xe, phân công xe & tài xế hàng ngày.',
    colorKey: 'blue',
    permissions: [
      'request.create',
      'request.approve',
      'request.fill_price',
      'request.paper.manage',
      'trip.assign',
      'trip.view_all',
      'trip.update_status',
      'trip.record.create',
      'trip.event.create',
      'trip.cost.view',
      'resource.driver.manage',
      'resource.vehicle.manage',
      'resource.provider.manage',
      'cargo.manage',
      'attachment.upload',
      'report.view',
    ],
  },
  {
    id: 'accountant',
    icon: '💰',
    title: 'Kế toán',
    description: 'Đối soát chi phí, công nợ và thực hiện thanh toán.',
    colorKey: 'green',
    permissions: [
      'trip.cost.view',
      'trip.cost.reconcile',
      'payment.reconcile',
      'payment.execute',
      'report.view',
      'report.export',
      'reference_pricing.manage',
      'attachment.upload',
    ],
  },
  {
    id: 'internal_user',
    icon: '👤',
    title: 'Nhân viên nội bộ',
    description: 'Tạo và theo dõi yêu cầu điều xe cơ bản.',
    colorKey: 'slate',
    permissions: [
      'request.create',
      'request.update_own',
      'request.cancel_own',
      'trip.view_own',
      'attachment.upload',
    ],
  },
  {
    id: 'dept_head',
    icon: '👔',
    title: 'Trưởng đơn vị',
    description: 'Duyệt phiếu điều xe và theo dõi yêu cầu của phòng ban.',
    colorKey: 'amber',
    permissions: [
      'request.create',
      'request.update_own',
      'request.cancel_own',
      'request.approve_dept',
      'trip.view_own',
      'attachment.upload',
      'report.view',
    ],
  },
  {
    id: 'driver',
    icon: '🚗',
    title: 'Tài xế',
    description: 'Xem lịch trình và cập nhật trạng thái chuyến được giao.',
    colorKey: 'teal',
    permissions: [
      'trip.view_own',
      'trip.update_status',
      'trip.record.create',
      'trip.event.create',
      'trip.cost.view',
      'attachment.upload',
    ],
  },
  {
    id: 'hr',
    icon: '🧑‍💼',
    title: 'Nhân sự',
    description: 'Quản lý tài khoản và gán vai trò cho nhân viên.',
    colorKey: 'indigo',
    permissions: [
      'user.manage',
      'system.user_roles.manage',
      'report.view',
      'attachment.upload',
    ],
  },
  {
    id: 'p2p_coordinator',
    icon: '🏫',
    title: 'Điều phối P2P',
    description: 'Quản lý chuyến đưa đón và học sinh chính sách.',
    colorKey: 'purple',
    permissions: [
      'policy_trip.view',
      'policy_trip.assign_driver',
      'policy_trip.cancel',
      'student_policy.manage',
      'school_calendar.manage',
      'route.manage',
      'student.manage',
      'resource.driver.manage',
      'resource.vehicle.manage',
      'attachment.upload',
      'report.view',
    ],
  },
  {
    id: 'admin',
    icon: '🛡️',
    title: 'Quản trị hệ thống',
    description: 'Toàn quyền cấu hình hệ thống. Dành cho IT / Admin.',
    colorKey: 'violet',
    permissions: [
      'request.create', 'request.update_own', 'request.cancel_own',
      'request.approve', 'request.fill_price', 'request.approve_dept', 'request.paper.manage',
      'trip.assign', 'trip.view_all', 'trip.update_status',
      'trip.record.create', 'trip.event.create',
      'trip.cost.view', 'trip.cost.reconcile',
      'payment.reconcile', 'payment.execute',
      'cargo.manage', 'route.manage', 'student.manage',
      'attachment.upload', 'report.view', 'report.export',
      'reference_pricing.manage',
      'resource.driver.manage', 'resource.vehicle.manage', 'resource.provider.manage',
      'user.manage', 'audit_log.view', 'data.override_confirmed',
      'system.roles.manage', 'system.permissions.manage',
      'system.user_roles.manage', 'system.feature_toggles.manage',
      'dispatch.settings.manage',
      'policy_trip.view', 'policy_trip.assign_driver', 'policy_trip.cancel',
      'student_policy.manage', 'school_calendar.manage',
    ],
  },
  {
    id: 'custom',
    icon: '✨',
    title: 'Tùy chỉnh',
    description: 'Tự thiết lập quyền theo nhu cầu đặc thù của tổ chức.',
    colorKey: 'rose',
    permissions: [],
  },
])

/** Return a template by id. */
export function getTemplate(id) {
  return JOB_ROLE_TEMPLATES.find((t) => t.id === id) ?? null
}

/** Color map (same keys as businessCapabilities.js). */
export const TEMPLATE_COLOR_CLASSES = Object.freeze({
  blue:   { selected: 'ring-2 ring-blue-400 border-blue-400 bg-blue-50 dark:bg-blue-950/40 dark:border-blue-600',   idle: 'border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700',   icon: 'bg-blue-100 dark:bg-blue-900/50',   text: 'text-blue-700 dark:text-blue-300'   },
  teal:   { selected: 'ring-2 ring-teal-400 border-teal-400 bg-teal-50 dark:bg-teal-950/40 dark:border-teal-600',   idle: 'border-slate-200 dark:border-slate-700 hover:border-teal-300 dark:hover:border-teal-700',   icon: 'bg-teal-100 dark:bg-teal-900/50',   text: 'text-teal-700 dark:text-teal-300'   },
  amber:  { selected: 'ring-2 ring-amber-400 border-amber-400 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-600', idle: 'border-slate-200 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-700', icon: 'bg-amber-100 dark:bg-amber-900/50', text: 'text-amber-700 dark:text-amber-300' },
  green:  { selected: 'ring-2 ring-green-400 border-green-400 bg-green-50 dark:bg-green-950/40 dark:border-green-600', idle: 'border-slate-200 dark:border-slate-700 hover:border-green-300 dark:hover:border-green-700', icon: 'bg-green-100 dark:bg-green-900/50', text: 'text-green-700 dark:text-green-300' },
  indigo: { selected: 'ring-2 ring-indigo-400 border-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 dark:border-indigo-600', idle: 'border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700', icon: 'bg-indigo-100 dark:bg-indigo-900/50', text: 'text-indigo-700 dark:text-indigo-300' },
  purple: { selected: 'ring-2 ring-purple-400 border-purple-400 bg-purple-50 dark:bg-purple-950/40 dark:border-purple-600', idle: 'border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700', icon: 'bg-purple-100 dark:bg-purple-900/50', text: 'text-purple-700 dark:text-purple-300' },
  violet: { selected: 'ring-2 ring-violet-400 border-violet-400 bg-violet-50 dark:bg-violet-950/40 dark:border-violet-600', idle: 'border-slate-200 dark:border-slate-700 hover:border-violet-300 dark:hover:border-violet-700', icon: 'bg-violet-100 dark:bg-violet-900/50', text: 'text-violet-700 dark:text-violet-300' },
  rose:   { selected: 'ring-2 ring-rose-400 border-rose-400 bg-rose-50 dark:bg-rose-950/40 dark:border-rose-600',   idle: 'border-slate-200 dark:border-slate-700 hover:border-rose-300 dark:hover:border-rose-700',   icon: 'bg-rose-100 dark:bg-rose-900/50',   text: 'text-rose-700 dark:text-rose-300'   },
  slate:  { selected: 'ring-2 ring-slate-400 border-slate-400 bg-slate-100 dark:bg-slate-800 dark:border-slate-500', idle: 'border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500',  icon: 'bg-slate-200 dark:bg-slate-700',    text: 'text-slate-700 dark:text-slate-300' },
})
