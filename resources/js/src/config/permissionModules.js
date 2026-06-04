/**
 * PERMISSION_MODULES — canonical grouping of all permission keys by functional domain.
 *
 * The `prefixes` array lists exact permission name prefixes used for assignment.
 * A permission belongs to the first module whose prefix matches.
 *
 * Keep in sync with RbacSeeder.php when new permissions are added.
 */
export const PERMISSION_MODULES = Object.freeze([
  {
    id: 'request',
    label: 'Yêu cầu điều xe',
    icon: 'ClipboardDocumentListIcon',
    prefixes: ['request.'],
  },
  {
    id: 'trip',
    label: 'Chuyến',
    icon: 'TruckIcon',
    // trip.cost.* handled by its own module — list explicit non-cost prefixes first
    prefixes: ['trip.assign', 'trip.view_all', 'trip.view_own', 'trip.update_status', 'trip.record.', 'trip.event.'],
  },
  {
    id: 'trip_cost',
    label: 'Chi phí chuyến',
    icon: 'CurrencyDollarIcon',
    prefixes: ['trip.cost.'],
  },
  {
    id: 'payment',
    label: 'Thanh toán',
    icon: 'BanknotesIcon',
    prefixes: ['payment.'],
  },
  {
    id: 'report',
    label: 'Báo cáo',
    icon: 'ChartBarIcon',
    prefixes: ['report.'],
  },
  {
    id: 'cargo',
    label: 'Hàng hóa (Cargo)',
    icon: 'ArchiveBoxIcon',
    prefixes: ['cargo.'],
  },
  {
    id: 'd2d',
    label: 'Tuyến D2D & Học sinh',
    icon: 'MapIcon',
    prefixes: ['route.', 'student.'],
  },
  {
    id: 'p2p_policy',
    label: 'Học sinh Chính Sách (P2P)',
    icon: 'AcademicCapIcon',
    prefixes: ['policy_trip.', 'student_policy.', 'school_calendar.'],
  },
  {
    id: 'resource',
    label: 'Tài nguyên',
    icon: 'WrenchScrewdriverIcon',
    prefixes: ['resource.', 'reference_pricing.'],
  },
  {
    id: 'ops',
    label: 'Vận hành',
    icon: 'CogIcon',
    prefixes: ['attachment.', 'data.', 'dispatch.settings.', 'audit_log.'],
  },
  {
    id: 'users',
    label: 'Người dùng',
    icon: 'UsersIcon',
    prefixes: ['user.'],
  },
  {
    id: 'system',
    label: 'Hệ thống',
    icon: 'ShieldCheckIcon',
    prefixes: ['system.'],
  },
])

/**
 * Returns the module id that a given permission name belongs to.
 * Falls back to 'ops' if no module matches.
 *
 * @param {string} permName
 * @returns {string} module id
 */
export function getModuleId(permName) {
  for (const mod of PERMISSION_MODULES) {
    for (const prefix of mod.prefixes) {
      if (permName === prefix || permName.startsWith(prefix)) {
        return mod.id
      }
    }
  }
  return 'ops'
}

/**
 * Groups an array of permission objects by module.
 *
 * @param {Array<{id: number|string, name: string, display_name?: string, plain_description?: string}>} perms
 * @returns {Array<{module: object, perms: Array}>} ordered list of { module, perms } pairs
 */
export function groupPermissions(perms) {
  const byModule = new Map(PERMISSION_MODULES.map((m) => [m.id, { module: m, perms: [] }]))

  for (const perm of perms) {
    const modId = getModuleId(perm.name)
    const entry = byModule.get(modId)
    if (entry) {
      entry.perms.push(perm)
    }
  }

  // Return only modules that have at least one permission
  return [...byModule.values()].filter((e) => e.perms.length > 0)
}
