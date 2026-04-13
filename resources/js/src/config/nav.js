/**
 * Mục sidebar / drawer.
 * `perms`: metadata cho lọc theo quyền (hiện chưa áp dụng trong UI; sẽ dùng sau).
 * `icon`: khóa trong `navIconMap.js`
 * `badgeKey`: khóa trả về từ GET /api/nav/badges (frontend map số hiển thị)
 * `sectionKey`: nhóm accordion (null = luôn mở, không có tiêu đề section)
 * `children`: mảng mục con (không có `to` ở mục cha); sidebar ngang: dropdown / nhóm trong panel.
 */
export const NAV_SECTIONS = [
  {
    sectionKey: null,
    headingKey: null,
    items: [
      { to: '/', labelKey: 'nav.dashboard', perms: [], icon: 'home' },
      { to: '/hub', labelKey: 'nav.hub', perms: [], icon: 'hub' },
      {
        labelKey: 'nav.group_schedule',
        perms: [],
        icon: 'schedule',
        children: [
          {
            to: '/schedule',
            labelKey: 'nav.schedule7',
            perms: ['trip.view_all', 'trip.view_own', 'trip.assign'],
            icon: 'schedule',
          },
          {
            to: '/calendar',
            labelKey: 'nav.calendar',
            perms: ['trip.view_all', 'trip.view_own', 'trip.assign'],
            icon: 'calendar',
          },
        ],
      },
      { to: '/map', labelKey: 'nav.map', perms: [], icon: 'map' },
    ],
  },
  {
    sectionKey: 'ops',
    headingKey: 'nav.section_ops',
    items: [
      {
        to: '/requests',
        labelKey: 'nav.requests',
        perms: ['request.create', 'request.approve', 'request.paper.manage', 'trip.assign'],
        icon: 'requests',
        badgeKey: 'pending_dispatch_requests',
      },
      { to: '/dispatch-requests/new', labelKey: 'nav.new_request', perms: ['request.create'], icon: 'new_request' },
      { to: '/trips', labelKey: 'nav.trips', perms: ['trip.view_all', 'trip.view_own', 'trip.assign'], icon: 'trips' },
      { to: '/costs', labelKey: 'nav.costs', perms: ['trip.cost.view', 'trip.cost.reconcile', 'trip.record.create'], icon: 'costs' },
      {
        to: '/cargo',
        labelKey: 'nav.cargo',
        perms: ['cargo.manage', 'trip.assign'],
        icon: 'cargo',
        badgeKey: 'cargo_sla_breaches',
      },
      { to: '/routes', labelKey: 'nav.routes', perms: ['route.manage', 'student.manage'], icon: 'routes' },
      { to: '/students', labelKey: 'nav.students', perms: ['student.manage'], icon: 'students' },
    ],
  },
  {
    sectionKey: 'finance',
    headingKey: 'nav.section_finance',
    items: [
      { to: '/payments', labelKey: 'nav.payments', perms: ['payment.reconcile', 'payment.execute', 'trip.cost.reconcile'], icon: 'payments' },
    ],
  },
  {
    sectionKey: 'reports',
    headingKey: 'nav.section_reports',
    items: [
      { to: '/reports', labelKey: 'nav.reports', perms: ['report.view', 'report.export'], icon: 'reports' },
      { to: '/pricing', labelKey: 'nav.pricing', perms: [], icon: 'pricing' },
    ],
  },
  {
    sectionKey: 'help',
    headingKey: 'nav.section_help',
    items: [
      { to: '/help', labelKey: 'nav.help', perms: [], icon: 'help' },
      { to: '/roadmap', labelKey: 'nav.roadmap', perms: [], icon: 'roadmap' },
      {
        to: '/notifications',
        labelKey: 'nav.notifications',
        perms: [],
        icon: 'notifications',
        badgeKey: 'notifications_unread',
      },
    ],
  },
  {
    sectionKey: 'system',
    headingKey: 'nav.section_system',
    items: [{ to: '/audit-logs', labelKey: 'nav.audit', perms: ['audit_log.view'], icon: 'audit' }],
  },
]

/** Bottom bar (mobile): rút gọn; [] = luôn hiện */
export const BOTTOM_NAV = [
  { to: '/', labelKey: 'nav.bottom_home', perms: [], icon: 'home' },
  { to: '/hub', labelKey: 'nav.bottom_hub', perms: [], icon: 'hub' },
  {
    to: '/requests',
    labelKey: 'nav.bottom_requests',
    perms: ['request.create', 'request.approve', 'request.paper.manage', 'trip.assign'],
    icon: 'requests',
    badgeKey: 'pending_dispatch_requests',
  },
  { to: '/trips', labelKey: 'nav.bottom_trips', perms: ['trip.view_all', 'trip.view_own', 'trip.assign'], icon: 'trips' },
  { to: '/schedule', labelKey: 'nav.bottom_schedule', perms: ['trip.view_all', 'trip.view_own', 'trip.assign'], icon: 'schedule' },
]
