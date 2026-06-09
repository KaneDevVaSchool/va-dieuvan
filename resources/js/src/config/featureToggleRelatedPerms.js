/**
 * Quyền nghiệp vụ gắn với từng công tắc tính năng (gợi ý cho admin, không thay RBAC).
 * Nhãn hiển thị lấy từ businessCapabilities.js.
 */
export const FEATURE_TOGGLE_RELATED_PERMS = Object.freeze({
  'module.finance': ['payment.reconcile', 'payment.execute'],
  'module.reports': ['report.view', 'report.export'],
  'module.pricing': ['reference_pricing.manage'],
  'module.operations': [
    'trip.assign',
    'trip.cost.view',
    'trip.cost.reconcile',
    'resource.vehicle.manage',
    'resource.driver.manage',
    'resource.provider.manage',
  ],
  'module.system.roles': ['system.roles.manage'],
  'module.system.permissions': ['system.permissions.manage'],
  'module.system.user_roles': ['system.user_roles.manage'],
  'module.system.feature_toggles': ['system.feature_toggles.manage'],
  'module.system.dispatch_settings': ['dispatch.settings.manage'],
  'module.system.audit': ['audit_log.view'],
})
