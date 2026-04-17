import { i18n } from '../i18n'

/**
 * Cập nhật document.title theo route + locale (gọi sau đổi ngôn ngữ).
 */
export function applyRouteDocumentTitle(meta, routeName) {
  if (typeof document === 'undefined') return
  const appTitle = i18n.global.t('app.title')
  const key = routeName ? `routes_meta.${String(routeName)}.title` : null
  const pageTitle =
    key && i18n.global.te(key) ? i18n.global.t(key) : (meta?.title != null ? String(meta.title) : '')
  const isLogin = meta?.public && routeName === 'login'
  if (isLogin) {
    document.title = pageTitle ? `${pageTitle} · ${appTitle}` : appTitle
    return
  }
  document.title = pageTitle ? `${pageTitle} · ${appTitle}` : appTitle
}
