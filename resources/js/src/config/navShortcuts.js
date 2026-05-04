import { DISPATCH_WEB_BASE } from './dispatchWebBase'

/** Sau khi bấm `g`, ký tự tiếp theo trong ~1s (tránh xung đột ô nhập liệu). */

export const NAV_SHORTCUT_PREFIX = 'g'

export const NAV_SHORTCUT_ROUTES = {
  d: DISPATCH_WEB_BASE,
  h: `${DISPATCH_WEB_BASE}/dispatcher`,
  r: `${DISPATCH_WEB_BASE}/requests`,
  t: `${DISPATCH_WEB_BASE}/trips`,
  o: `${DISPATCH_WEB_BASE}/costs`,
  a: `${DISPATCH_WEB_BASE}/audit-logs`,
}

export const SHORTCUT_WINDOW_MS = 1000
