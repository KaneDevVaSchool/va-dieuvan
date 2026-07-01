/**
 * BUSINESS_CAPABILITY_GROUPS
 *
 * Maps raw permission keys → human-readable "capability cards" grouped by
 * business domain. Used in the role editor and permission matrix.
 * Hides all technical permission names from business users.
 *
 * Keep in sync with SEED_PERMISSION_PRESETS in systemSeedOptions.js
 * and RbacSeeder.php when permissions are added/removed.
 */

export const BUSINESS_CAPABILITY_GROUPS = Object.freeze([
  {
    id: 'request',
    icon: '📋',
    title: 'Yêu cầu điều xe',
    description: 'Tạo, duyệt và theo dõi phiếu yêu cầu điều xe.',
    colorKey: 'blue',
    capabilities: [
      { perm: 'request.create',        label: 'Tạo yêu cầu mới' },
      { perm: 'request.update_own',    label: 'Sửa yêu cầu của mình' },
      { perm: 'request.cancel_own',    label: 'Hủy yêu cầu của mình' },
      { perm: 'request.fill_price',    label: 'Điền giá dịch vụ vào phiếu' },
      { perm: 'request.approve',       label: 'Duyệt / từ chối yêu cầu' },
      { perm: 'request.approve_dept',  label: 'Duyệt phiếu (Trưởng đơn vị)' },
      { perm: 'request.paper.manage',  label: 'Quản lý phiếu giấy' },
    ],
  },
  {
    id: 'trip',
    icon: '🚌',
    title: 'Chuyến đi',
    description: 'Phân công xe, tài xế và theo dõi chuyến vận chuyển.',
    colorKey: 'teal',
    capabilities: [
      { perm: 'trip.assign',          label: 'Phân công xe & tài xế cho chuyến' },
      { perm: 'trip.view_all',        label: 'Xem tất cả chuyến đi' },
      { perm: 'trip.view_own',        label: 'Xem chuyến được giao cho mình' },
      { perm: 'trip.update_status',   label: 'Cập nhật trạng thái chuyến' },
      { perm: 'trip.record.create',   label: 'Thêm ghi chú vận hành' },
      { perm: 'trip.event.create',    label: 'Ghi nhận sự kiện trên chuyến' },
    ],
  },
  {
    id: 'trip_cost',
    icon: '💰',
    title: 'Chi phí chuyến',
    description: 'Xem và đối chiếu chi phí phát sinh trong chuyến.',
    colorKey: 'amber',
    capabilities: [
      { perm: 'trip.cost.view',       label: 'Xem chi phí chuyến' },
      { perm: 'trip.cost.reconcile',  label: 'Duyệt & đối chiếu chi phí' },
    ],
  },
  {
    id: 'payment',
    icon: '🏦',
    title: 'Thanh toán & Công nợ',
    description: 'Đối soát kỳ thanh toán và ghi nhận thanh toán.',
    colorKey: 'green',
    capabilities: [
      { perm: 'payment.reconcile', label: 'Đối soát thanh toán / công nợ' },
      { perm: 'payment.execute',   label: 'Ghi nhận & thực hiện thanh toán' },
    ],
  },
  {
    id: 'resources',
    icon: '🚚',
    title: 'Phương tiện & Đối tác',
    description: 'Quản lý danh sách tài xế, xe và nhà xe đối tác.',
    colorKey: 'orange',
    capabilities: [
      { perm: 'resource.driver.manage',    label: 'Quản lý tài xế' },
      { perm: 'resource.vehicle.manage',   label: 'Quản lý xe / phương tiện' },
      { perm: 'resource.provider.manage',  label: 'Quản lý nhà xe / đối tác' },
      { perm: 'reference_pricing.manage',  label: 'Chỉnh sửa bảng giá tham chiếu' },
    ],
  },
  {
    id: 'cargo',
    icon: '📦',
    title: 'Hàng hóa (Cargo)',
    description: 'Quản lý thông tin kiện hàng và SLA giao nhận.',
    colorKey: 'slate',
    capabilities: [
      { perm: 'cargo.manage', label: 'Quản lý thông tin hàng hóa' },
    ],
  },
  {
    id: 'reports',
    icon: '📊',
    title: 'Báo cáo & Phân tích',
    description: 'Xem tổng hợp và xuất báo cáo.',
    colorKey: 'cyan',
    capabilities: [
      { perm: 'report.view',   label: 'Xem báo cáo tổng hợp' },
      { perm: 'report.export', label: 'Xuất báo cáo (Excel / PDF)' },
    ],
  },
  {
    id: 'user_mgmt',
    icon: '👥',
    title: 'Quản lý nhân sự',
    description: 'Thêm, sửa và quản lý tài khoản người dùng trong hệ thống.',
    colorKey: 'rose',
    capabilities: [
      { perm: 'user.manage', label: 'Quản lý tài khoản nhân viên' },
    ],
  },
  {
    id: 'ops',
    icon: '⚙️',
    title: 'Vận hành hệ thống',
    description: 'Tải file, cài đặt vận hành và ghi đè dữ liệu.',
    colorKey: 'slate',
    capabilities: [
      { perm: 'attachment.upload',         label: 'Tải file đính kèm' },
      { perm: 'data.override_confirmed',   label: 'Ghi đè dữ liệu đã xác nhận' },
      { perm: 'dispatch.settings.manage',  label: 'Điều chỉnh ngưỡng "Gấp"' },
    ],
  },
  {
    id: 'transport_program',
    icon: '🎒',
    title: 'Vận chuyển học sinh',
    description: 'Chương trình TP, học sinh, điểm danh và import.',
    colorKey: 'indigo',
    capabilities: [
      { perm: 'tp_program.view',              label: 'Xem chương trình & lịch ngày' },
      { perm: 'tp_program.manage',            label: 'Quản lý chương trình vận chuyển' },
      { perm: 'tp_student.view',              label: 'Xem danh sách học sinh' },
      { perm: 'tp_student.manage',            label: 'Thêm / sửa học sinh' },
      { perm: 'tp_enrollment.manage',         label: 'Ghi danh học sinh vào chương trình' },
      { perm: 'tp_attendance.manage',         label: 'Điểm danh & báo vắng' },
      { perm: 'tp_attendance.confirm',        label: 'Xác nhận điểm danh đã khóa' },
      { perm: 'tp_driver_assign.manage',      label: 'Gán tài xế theo ngày / ca' },
      { perm: 'tp_cost.manage',               label: 'Quản lý chi phí TP' },
      { perm: 'tp_import.manage',             label: 'Import học sinh từ Excel' },
      { perm: 'tp_report.view',               label: 'Báo cáo TP (vắng, chi phí)' },
      { perm: 'tp_audit.view',                label: 'Nhật ký thao tác TP' },
      { perm: 'tp_execution.force_complete',  label: 'Buộc hoàn thành chuyến TP' },
    ],
  },
  {
    id: 'admin',
    icon: '🛡️',
    title: 'Quản trị hệ thống',
    description: 'Cấu hình vai trò, quyền và tính năng toàn hệ thống.',
    colorKey: 'violet',
    capabilities: [
      { perm: 'system.roles.manage',           label: 'Quản lý vai trò & phân quyền' },
      { perm: 'system.permissions.manage',     label: 'Quản lý danh sách quyền chi tiết' },
      { perm: 'system.user_roles.manage',      label: 'Gán vai trò cho người dùng' },
      { perm: 'system.feature_toggles.manage', label: 'Bật / tắt tính năng hệ thống' },
      { perm: 'audit_log.view',                label: 'Xem nhật ký hoạt động' },
    ],
  },
])

/** Color class maps for Tailwind static analysis (no dynamic class strings). */
export const COLOR_CLASSES = Object.freeze({
  blue:   { bg: 'bg-blue-50 dark:bg-blue-950/30',   border: 'border-blue-200 dark:border-blue-800/50',   text: 'text-blue-700 dark:text-blue-300',   badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',   ring: 'ring-blue-300 dark:ring-blue-700'   },
  teal:   { bg: 'bg-teal-50 dark:bg-teal-950/30',   border: 'border-teal-200 dark:border-teal-800/50',   text: 'text-teal-700 dark:text-teal-300',   badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-200',   ring: 'ring-teal-300 dark:ring-teal-700'   },
  amber:  { bg: 'bg-amber-50 dark:bg-amber-950/30', border: 'border-amber-200 dark:border-amber-800/50', text: 'text-amber-700 dark:text-amber-300', badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200', ring: 'ring-amber-300 dark:ring-amber-700' },
  green:  { bg: 'bg-green-50 dark:bg-green-950/30', border: 'border-green-200 dark:border-green-800/50', text: 'text-green-700 dark:text-green-300', badge: 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200', ring: 'ring-green-300 dark:ring-green-700' },
  orange: { bg: 'bg-orange-50 dark:bg-orange-950/30', border: 'border-orange-200 dark:border-orange-800/50', text: 'text-orange-700 dark:text-orange-300', badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/60 dark:text-orange-200', ring: 'ring-orange-300 dark:ring-orange-700' },
  indigo: { bg: 'bg-indigo-50 dark:bg-indigo-950/30', border: 'border-indigo-200 dark:border-indigo-800/50', text: 'text-indigo-700 dark:text-indigo-300', badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-200', ring: 'ring-indigo-300 dark:ring-indigo-700' },
  purple: { bg: 'bg-purple-50 dark:bg-purple-950/30', border: 'border-purple-200 dark:border-purple-800/50', text: 'text-purple-700 dark:text-purple-300', badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/60 dark:text-purple-200', ring: 'ring-purple-300 dark:ring-purple-700' },
  violet: { bg: 'bg-violet-50 dark:bg-violet-950/30', border: 'border-violet-200 dark:border-violet-800/50', text: 'text-violet-700 dark:text-violet-300', badge: 'bg-violet-100 text-violet-800 dark:bg-violet-900/60 dark:text-violet-200', ring: 'ring-violet-300 dark:ring-violet-700' },
  rose:   { bg: 'bg-rose-50 dark:bg-rose-950/30',   border: 'border-rose-200 dark:border-rose-800/50',   text: 'text-rose-700 dark:text-rose-300',   badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-200',   ring: 'ring-rose-300 dark:ring-rose-700'   },
  cyan:   { bg: 'bg-cyan-50 dark:bg-cyan-950/30',   border: 'border-cyan-200 dark:border-cyan-800/50',   text: 'text-cyan-700 dark:text-cyan-300',   badge: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/60 dark:text-cyan-200',   ring: 'ring-cyan-300 dark:ring-cyan-700'   },
  slate:  { bg: 'bg-slate-50 dark:bg-slate-800/40', border: 'border-slate-200 dark:border-slate-700',    text: 'text-slate-700 dark:text-slate-300', badge: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',   ring: 'ring-slate-300 dark:ring-slate-600' },
})

/** Get color classes for a given colorKey. Falls back to slate. */
export function getColorClasses(colorKey) {
  return COLOR_CLASSES[colorKey] ?? COLOR_CLASSES.slate
}

/** All permission names defined across all capability groups. */
export function getAllCapabilityPerms() {
  return BUSINESS_CAPABILITY_GROUPS.flatMap((g) => g.capabilities.map((c) => c.perm))
}

/** Set of every permission name mapped to a capability group. */
const COVERED_PERM_SET = new Set(getAllCapabilityPerms())

/** True when a permission name is mapped to some capability group. */
export function isPermCovered(permName) {
  return COVERED_PERM_SET.has(permName)
}

/**
 * Permission objects that are NOT mapped to any capability group.
 * Guards against config drift: any perm added to the backend but not yet
 * grouped here stays editable instead of becoming invisible.
 *
 * @param {Array<{id?: any, name: string, display_name?: string}>} allPerms
 */
export function getUncoveredPerms(allPerms) {
  return (allPerms ?? []).filter((p) => p && !COVERED_PERM_SET.has(p.name))
}

const CAPABILITY_LABEL_BY_PERM = new Map(
  BUSINESS_CAPABILITY_GROUPS.flatMap((g) => g.capabilities.map((c) => [c.perm, c.label])),
)

/** Nhãn ngắn, dễ hiểu cho admin (ẩn mã kỹ thuật trên UI). */
export function getCapabilityLabelForPerm(permName) {
  if (!permName) return null
  return CAPABILITY_LABEL_BY_PERM.get(permName) ?? null
}

/** Tiêu đề hiển thị: capability → display_name DB → mã quyền. */
export function getPermissionFriendlyTitle(perm) {
  const name = typeof perm === 'string' ? perm : perm?.name
  const fromCap = getCapabilityLabelForPerm(name)
  if (fromCap) return fromCap
  const dn = typeof perm === 'object' ? perm?.display_name?.trim() : ''
  if (dn && dn !== name) return dn
  return name ?? ''
}

/** Get the capability group containing a given permission name. */
export function getGroupForPerm(permName) {
  for (const group of BUSINESS_CAPABILITY_GROUPS) {
    if (group.capabilities.some((c) => c.perm === permName)) return group
  }
  return null
}

/**
 * Given a Set of selected permission names, compute per-group selection stats.
 * Returns: Map<groupId, { selected: number, total: number }>
 */
export function computeGroupStats(selectedPermNames) {
  const stats = new Map()
  for (const group of BUSINESS_CAPABILITY_GROUPS) {
    const total = group.capabilities.length
    const selected = group.capabilities.filter((c) => selectedPermNames.has(c.perm)).length
    stats.set(group.id, { selected, total })
  }
  return stats
}
