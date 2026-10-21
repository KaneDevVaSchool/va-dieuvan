export function canImpersonate(authUser, user) {
  return !!authUser?.can_impersonate && !authUser?.impersonation?.active && user.id !== authUser.id
    && user.is_active !== false && user.is_active !== 0 && !user.is_superadmin
    && !(user.roles ?? []).some(role => role.name === 'superadmin')
}
