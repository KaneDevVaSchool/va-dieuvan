/**
 * Mục sidebar / drawer.
 * `icon`: khóa trong `navIconMap.js`
 * `badgeKey`: khóa trả về từ GET /api/nav/badges (frontend map số hiển thị)
 * `sectionKey`: nhóm accordion (null = luôn mở, không có tiêu đề section)
 * `children`: mảng mục con (không có `to` ở mục cha); sidebar ngang: dropdown / nhóm trong panel.
 * `featureKey` / `permissionKey`: lọc theo feature toggle & RBAC (xem `useNavSections`).
 */
export const NAV_SECTIONS = [
  {
    sectionKey: null,
    headingKey: null,
    items: [
      {
        labelKey: 'nav.group_overview',
        icon: 'home',
        featureKey: 'module.overview',
        children: [
          { to: '/', labelKey: 'nav.dashboard', icon: 'home', featureKey: 'module.overview' },
          { to: '/dispatcher', labelKey: 'nav.dispatcher_board', icon: 'dispatcher', featureKey: 'module.overview' },
          {
            to: '/resources',
            labelKey: 'nav.resources_overview',
            icon: 'resources',
            featureKey: 'module.operations',
          },
        ],
      },
    ],
  },
  {
    sectionKey: 'ops',
    headingKey: 'nav.section_ops',
    items: [
      {
        to: '/requests',
        labelKey: 'nav.requests',
        icon: 'requests',
        badgeKey: 'pending_dispatch_requests',
        featureKey: 'module.operations',
      },
      { to: '/trips', labelKey: 'nav.trips', icon: 'trips', featureKey: 'module.operations' },
      { to: '/costs', labelKey: 'nav.costs', icon: 'costs', featureKey: 'module.operations' },
      {
        to: '/cargo',
        labelKey: 'nav.cargo',
        icon: 'cargo',
        badgeKey: 'cargo_sla_breaches',
        featureKey: 'module.operations',
      },
      { to: '/routes', labelKey: 'nav.routes', icon: 'routes', featureKey: 'module.operations' },
      {
        to: '/resources/list',
        labelKey: 'nav.resources',
        icon: 'resources',
        featureKey: 'module.operations',
      },
    ],
  },
  {
    sectionKey: 'reports',
    headingKey: 'nav.section_reports',
    items: [
      { to: '/reports', labelKey: 'nav.reports', icon: 'reports', featureKey: 'module.reports' },
      { to: '/payments', labelKey: 'nav.payments', icon: 'payments', featureKey: 'module.finance' },
      { to: '/pricing', labelKey: 'nav.pricing', icon: 'pricing', featureKey: 'module.reports' },
    ],
  },
  {
    sectionKey: 'system',
    headingKey: 'nav.section_system',
    items: [
      {
        labelKey: 'nav.group_system',
        icon: 'settings',
        children: [
          {
            to: '/system/roles',
            labelKey: 'nav.system_roles',
            icon: 'roles',
            featureKey: 'module.system.roles',
            permissionKey: 'system.roles.manage',
          },
          {
            to: '/system/permissions',
            labelKey: 'nav.system_permissions',
            icon: 'permissions',
            featureKey: 'module.system.permissions',
            permissionKey: 'system.permissions.manage',
          },
          {
            to: '/system/user-roles',
            labelKey: 'nav.system_user_roles',
            icon: 'user_roles',
            featureKey: 'module.system.user_roles',
            permissionKey: 'system.user_roles.manage',
          },
          {
            to: '/system/feature-toggles',
            labelKey: 'nav.system_feature_toggles',
            icon: 'feature_toggle',
            featureKey: 'module.system.feature_toggles',
            permissionKey: 'system.feature_toggles.manage',
          },
          {
            to: '/audit-logs',
            labelKey: 'nav.audit',
            icon: 'audit',
            featureKey: 'module.system.audit',
            permissionKey: 'audit_log.view',
          },
        ],
      },
    ],
  },
]

/** Bottom bar (mobile): rút gọn */
export const BOTTOM_NAV = [
  { to: '/', labelKey: 'nav.bottom_home', icon: 'home' },
  { to: '/dispatcher', labelKey: 'nav.bottom_dispatcher', icon: 'dispatcher' },
  {
    to: '/requests',
    labelKey: 'nav.bottom_requests',
    icon: 'requests',
    badgeKey: 'pending_dispatch_requests',
  },
  { to: '/trips', labelKey: 'nav.bottom_trips', icon: 'trips' },
  { to: '/costs', labelKey: 'nav.bottom_costs', icon: 'costs' },
]

/**
 * Các mục lá trên menu (đường dẫn + khóa bật/tắt) — dùng màn cài đặt & tài liệu vận hành.
 * @returns {{ to: string, labelKey: string, featureKey: string, sectionKey: string|null }[]}
 */
export function flattenNavLeaves() {
  const out = []
  for (const sec of NAV_SECTIONS) {
    for (const item of sec.items ?? []) {
      if (item.children?.length) {
        for (const c of item.children) {
          if (c.to) {
            out.push({
              to: c.to,
              labelKey: c.labelKey,
              featureKey: c.featureKey,
              sectionKey: sec.sectionKey ?? null,
            })
          }
        }
      } else if (item.to) {
        out.push({
          to: item.to,
          labelKey: item.labelKey,
          featureKey: item.featureKey,
          sectionKey: sec.sectionKey ?? null,
        })
      }
    }
  }
  return out
}

/**
 * Cụm sidebar ↔ khóa công tắc (màn /system/feature-toggles).
 * @returns {{ sectionKey: string|null, headingKey: string, features: { featureKey: string, links: { to: string, labelKey: string }[] }[] }[]}
 */
export function getFeatureToggleNavClusters() {
  const clusters = []
  for (const sec of NAV_SECTIONS) {
    /** @type {string[]} */
    const order = []
    /** @type {Map<string, { to: string, labelKey: string }[]>} */
    const byKey = new Map()

    function pushLeaf(node) {
      if (node.to && node.featureKey) {
        const k = node.featureKey
        if (!byKey.has(k)) {
          byKey.set(k, [])
          order.push(k)
        }
        byKey.get(k).push({ to: node.to, labelKey: node.labelKey })
      }
      if (node.children?.length) {
        for (const c of node.children) pushLeaf(c)
      }
    }

    for (const item of sec.items ?? []) {
      pushLeaf(item)
    }

    if (order.length === 0) continue

    clusters.push({
      sectionKey: sec.sectionKey ?? null,
      headingKey: sec.headingKey ?? 'nav.group_overview',
      features: order.map((featureKey) => ({
        featureKey,
        links: byKey.get(featureKey) ?? [],
      })),
    })
  }
  return clusters
}
