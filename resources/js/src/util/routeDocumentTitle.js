import { i18n } from '../i18n'

export function buildDocumentTitle(pageTitle) {
  return pageTitle ? `Điều Vận - ${pageTitle}` : 'Điều Vận'
}

/**
 * Cập nhật document.title theo route + locale (gọi sau đổi ngôn ngữ).
 */
export function applyRouteDocumentTitle(meta, routeName) {
  if (typeof document === 'undefined') return
  const key = routeName ? `routes_meta.${String(routeName)}.title` : null
  const pageTitle =
    key && i18n.global.te(key) ? i18n.global.t(key) : (meta?.title != null ? String(meta.title) : '')
  document.title = buildDocumentTitle(pageTitle)
}
