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
          { to: '/hub', labelKey: 'nav.hub', icon: 'hub', featureKey: 'module.overview' },
        ],
      },
      {
        labelKey: 'nav.group_schedule',
        icon: 'schedule',
        featureKey: 'module.overview',
        children: [
          {
            to: '/schedule',
            labelKey: 'nav.schedule7',
            icon: 'schedule',
            featureKey: 'module.overview',
          },
          {
            to: '/calendar',
            labelKey: 'nav.calendar',
            icon: 'calendar',
            featureKey: 'module.overview',
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
      {
        to: '/dispatch-requests/new',
        labelKey: 'nav.new_request',
        icon: 'new_request',
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
      { to: '/students', labelKey: 'nav.students', icon: 'students', featureKey: 'module.operations' },
    ],
  },
  {
    sectionKey: 'resources',
    headingKey: 'nav.section_resources',
    items: [
      {
        to: '/resources',
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
    sectionKey: 'help',
    headingKey: 'nav.section_help',
    items: [
      { to: '/help', labelKey: 'nav.help', icon: 'help', featureKey: 'module.help' },
      { to: '/roadmap', labelKey: 'nav.roadmap', icon: 'roadmap', featureKey: 'module.help' },
      {
        to: '/notifications',
        labelKey: 'nav.notifications',
        icon: 'notifications',
        badgeKey: 'notifications_unread',
        featureKey: 'module.help',
      },
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
  { to: '/hub', labelKey: 'nav.bottom_hub', icon: 'hub' },
  {
    to: '/requests',
    labelKey: 'nav.bottom_requests',
    icon: 'requests',
    badgeKey: 'pending_dispatch_requests',
  },
  { to: '/trips', labelKey: 'nav.bottom_trips', icon: 'trips' },
  { to: '/schedule', labelKey: 'nav.bottom_schedule', icon: 'schedule' },
]
