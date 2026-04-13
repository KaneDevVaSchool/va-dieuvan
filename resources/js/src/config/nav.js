/**
 * Mục sidebar / drawer.
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
      { to: '/', labelKey: 'nav.dashboard', icon: 'home' },
      { to: '/hub', labelKey: 'nav.hub', icon: 'hub' },
      {
        labelKey: 'nav.group_schedule',
        icon: 'schedule',
        children: [
          {
            to: '/schedule',
            labelKey: 'nav.schedule7',
            icon: 'schedule',
          },
          {
            to: '/calendar',
            labelKey: 'nav.calendar',
            icon: 'calendar',
          },
        ],
      },
      { to: '/map', labelKey: 'nav.map', icon: 'map' },
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
      },
      { to: '/dispatch-requests/new', labelKey: 'nav.new_request', icon: 'new_request' },
      { to: '/trips', labelKey: 'nav.trips', icon: 'trips' },
      { to: '/costs', labelKey: 'nav.costs', icon: 'costs' },
      {
        to: '/cargo',
        labelKey: 'nav.cargo',
        icon: 'cargo',
        badgeKey: 'cargo_sla_breaches',
      },
      { to: '/routes', labelKey: 'nav.routes', icon: 'routes' },
      { to: '/students', labelKey: 'nav.students', icon: 'students' },
    ],
  },
  {
    sectionKey: 'finance',
    headingKey: 'nav.section_finance',
    items: [{ to: '/payments', labelKey: 'nav.payments', icon: 'payments' }],
  },
  {
    sectionKey: 'reports',
    headingKey: 'nav.section_reports',
    items: [
      { to: '/reports', labelKey: 'nav.reports', icon: 'reports' },
      { to: '/pricing', labelKey: 'nav.pricing', icon: 'pricing' },
    ],
  },
  {
    sectionKey: 'help',
    headingKey: 'nav.section_help',
    items: [
      { to: '/help', labelKey: 'nav.help', icon: 'help' },
      { to: '/roadmap', labelKey: 'nav.roadmap', icon: 'roadmap' },
      {
        to: '/notifications',
        labelKey: 'nav.notifications',
        icon: 'notifications',
        badgeKey: 'notifications_unread',
      },
    ],
  },
  {
    sectionKey: 'system',
    headingKey: 'nav.section_system',
    items: [{ to: '/audit-logs', labelKey: 'nav.audit', icon: 'audit' }],
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
