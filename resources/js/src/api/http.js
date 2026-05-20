import { createHttpClient } from '../core/http/createHttpClient'
import { enqueueOutboxRequest } from '../core/offline/outbox'
import { TOKEN_KEY } from '../core/config/authKeys'
import { sanitizeLoginRedirect } from '../util/loginRedirect'

export const http = createHttpClient()

http.interceptors.response.use(
  (r) => r,
  async (err) => {
    if (err.response?.status === 401 && !err.config?.url?.includes('/login')) {
      localStorage.removeItem(TOKEN_KEY)
      try {
        localStorage.removeItem('vas_user_hint')
      } catch {
        /* ignore */
      }
      if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
        const back = sanitizeLoginRedirect(
          `${window.location.pathname || '/'}${window.location.search || ''}`,
        )
        window.location.href = `/login?redirect=${encodeURIComponent(back)}`
      }
    } else if (!err.response && err.config) {
      const code = err.code
      const maybeOffline =
        (typeof navigator !== 'undefined' && !navigator.onLine) || code === 'ERR_NETWORK'
      if (maybeOffline) {
        try {
          await enqueueOutboxRequest(err.config)
        } catch {
          /* ignore */
        }
      }
    }
    return Promise.reject(err)
  },
)

export { TOKEN_KEY }

/** Matches `http` client default — used when error config omits baseURL (some Axios/network failures). */
const DEFAULT_API_BASE = '/api'

/**
 * Build a readable request URL for error modals. Axios may omit `config` on some failures, or leave `url` empty after merge.
 * @param {import('axios').AxiosRequestConfig | undefined | null} cfg
 * @param {unknown} err
 */
function buildRequestUrlForErrorDetails(cfg, err) {
  const xhr = err && typeof err === 'object' ? err.request : null
  if (xhr && typeof xhr.responseURL === 'string' && xhr.responseURL) {
    return xhr.responseURL
  }

  if (!cfg || typeof cfg !== 'object') {
    // Không có axios config (ví dụ `throw new Error(...)` trong fetch) — không gán giả mặc định /api (gây hiểu nhầm GET /api).
    return '—'
  }

  const baseRaw =
    cfg.baseURL != null && String(cfg.baseURL).length > 0 ? String(cfg.baseURL) : DEFAULT_API_BASE
  const base = baseRaw.replace(/\/+$/, '') || DEFAULT_API_BASE.replace(/\/+$/, '')
  let u = cfg.url != null ? String(cfg.url) : ''
  if (u.startsWith('http')) {
    return u
  }
  const segment = u.startsWith('/') ? u : u ? `/${u}` : ''
  let path = `${base}${segment}`.replace(/([^:])\/{2,}/g, '$1/')
  if (!path || path === '//') {
    path = base || DEFAULT_API_BASE
  }

  const prm = cfg.params
  if (prm && typeof prm === 'object' && !Array.isArray(prm) && Object.keys(prm).length) {
    try {
      const qs = new URLSearchParams()
      for (const [k, v] of Object.entries(prm)) {
        if (v === undefined || v === null || v === '') continue
        qs.append(k, String(v))
      }
      const q = qs.toString()
      if (q) {
        path = `${path}${path.includes('?') ? '&' : '?'}${q}`
      }
    } catch {
      /* ignore */
    }
  }

  if (typeof window !== 'undefined' && path.startsWith('/')) {
    try {
      return new URL(path, window.location.origin).href
    } catch {
      return `${window.location.origin}${path}`
    }
  }
  return path
}

/** Vietnamese sentence markers — use to keep friendly messages from backend */
function looksLikeVietnamese(text) {
  return /[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/i.test(
    text || '',
  )
}

function humanizeStatusOnly(status) {
  if (status === 403) return 'Bạn không có quyền thực hiện thao tác này.'
  if (status === 404) return 'Không tìm thấy dữ liệu — có thể đã bị xóa hoặc bạn không có quyền xem.'
  if (status === 409) return 'Dữ liệu bị trùng hoặc xung đột. Vui lòng làm mới trang và thử lại.'
  if (status === 422) return 'Thông tin gửi lên chưa đúng. Vui lòng kiểm tra và nhập lại.'
  if (status === 429) return 'Bạn thao tác quá nhanh. Vui lòng đợi vài giây rồi thử lại.'
  if (status === 503) return 'Hệ thống đang bảo trì hoặc quá tải. Vui lòng thử lại sau.'
  if (status >= 500) return 'Hệ thống đang gặp sự cố tạm thời. Vui lòng thử lại sau hoặc báo quản trị viên.'
  return ''
}

/**
 * Turn server / Laravel English messages into short Vietnamese for end users.
 * @param {string} raw
 * @param {number} [status]
 */
export function humanizeApiMessage(raw, status) {
  const s = (typeof raw === 'string' ? raw : String(raw || '')).trim()
  if (!s) return humanizeStatusOnly(status) || ''

  const low = s.toLowerCase()

  if (/already exists|already been taken|duplicate entry/i.test(s)) {
    if (/email/i.test(s) && /already|taken|exists|duplicate/i.test(s)) {
      return 'Email này đã được dùng. Vui lòng nhập email khác.'
    }
    if (/permission|`[^`]+`.*permission|permission.*guard/i.test(s)) {
      return 'Quyền này đã có trong hệ thống. Không cần thêm lại; nếu cần, hãy đặt tên quyền khác.'
    }
    if (/role|`[^`]+`.*role|role.*guard/i.test(s)) {
      return 'Vai trò này đã có trong hệ thống. Hãy dùng tên khác hoặc chỉnh sửa vai trò hiện có.'
    }
    if (/key|toggle|feature|unique/i.test(low)) {
      return 'Mã định danh (key) này đã được dùng. Vui lòng chọn mã khác.'
    }
    return 'Thông tin này đã tồn tại, không thể tạo trùng.'
  }

  if (/foreign key|referenced|constraint fail|integrity constraint/i.test(low)) {
    return 'Không thể xóa hoặc thay đổi vì dữ liệu đang được dùng ở chỗ khác.'
  }

  if (/no query results for model|not found\b/i.test(low)) {
    return 'Không tìm thấy dữ liệu (có thể đã bị xóa).'
  }

  if (/this action is unauthorized|does not have the right permission/i.test(low)) {
    return 'Tài khoản của bạn không có quyền thực hiện thao tác này.'
  }

  if (/unauthenticated|token.*invalid|session expired/i.test(low)) {
    return 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'
  }

  if (/\bunauthorized\b/i.test(low) && status === 401) {
    return 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.'
  }

  if (/the .* field is required|field is required/i.test(low)) {
    return 'Vui lòng điền đầy đủ các ô bắt buộc còn thiếu (xem chi tiết từng mục bên dưới).'
  }

  if (looksLikeVietnamese(s)) return s

  if (status && humanizeStatusOnly(status)) {
    return humanizeStatusOnly(status)
  }

  return 'Không thực hiện được. Vui lòng kiểm tra lại thông tin hoặc thử sau.'
}

/**
 * Laravel 422 `errors` — tất cả thông báo theo field (không gộp một dòng chung).
 * @param {unknown} err
 * @returns {{ field: string, message: string }[]}
 */
export function extractApiValidationMessages(err) {
  const d = err?.response?.data
  if (!d?.errors || typeof d.errors !== 'object') return []
  /** @type {{ field: string, message: string }[]} */
  const out = []
  for (const [field, val] of Object.entries(d.errors)) {
    const list = Array.isArray(val) ? val : [val]
    for (const item of list) {
      if (item == null || item === '') continue
      out.push({ field: String(field), message: String(item) })
    }
  }
  return out
}

/** @param {unknown} err Axios-like error */
export function formatApiError(err, fallback = 'Đã xảy ra lỗi. Vui lòng thử lại.') {
  const status = err?.response?.status

  if (!err?.response) {
    const code = err?.code
    const msg = err?.message || ''
    if (code === 'ERR_NETWORK' || code === 'ECONNABORTED' || /network/i.test(msg)) {
      return 'Không kết nối được máy chủ. Kiểm tra internet và thử lại.'
    }
    return fallback
  }

  const d = err.response.data
  let raw = ''

  if (typeof d?.message === 'string' && d.message) {
    raw = d.message
  } else if (Array.isArray(d?.message) && d.message.length) {
    raw = String(d.message[0])
  } else if (d?.errors && typeof d.errors === 'object') {
    const all = extractApiValidationMessages(err)
    if (all.length === 1) {
      raw = all[0].message
    } else if (all.length > 1) {
      raw = all.map((x) => x.message).join(' ')
    } else {
      const first = Object.values(d.errors).flat()[0]
      if (first != null) raw = Array.isArray(first) ? first[0] : String(first)
    }
  }

  const human = humanizeApiMessage(raw, status)
  if (human) return human

  return humanizeStatusOnly(status) || fallback
}

/**
 * Title, friendly text, and technical details for global error modals (e.g. 429).
 * @param {unknown} err
 * @param {string} fallback
 */
export function buildApiErrorPresentation(err, fallback = 'Đã xảy ra lỗi.') {
  const friendly = formatApiError(err, fallback)
  const cfg = err?.response?.config || err?.config
  const status = err?.response?.status ?? null
  const headers = err?.response?.headers || {}

  let method = 'GET'
  if (cfg && typeof cfg === 'object') {
    method = String(cfg.method || 'get').toUpperCase()
  }

  const path = buildRequestUrlForErrorDetails(cfg, err)

  const axiosCode = err && typeof err === 'object' && 'code' in err ? String(err.code) : ''
  const axiosMessage =
    err && typeof err === 'object' && typeof err.message === 'string' ? err.message.slice(0, 800) : ''

  const retryRaw = headers['retry-after'] ?? headers['Retry-After']
  const retryAfter = retryRaw != null && retryRaw !== '' ? String(retryRaw) : null

  const d = err?.response?.data
  let serverRaw = ''
  if (typeof d?.message === 'string' && d.message) {
    serverRaw = d.message
  } else if (Array.isArray(d?.message) && d.message.length) {
    serverRaw = d.message.map(String).join(', ')
  }

  const friendlyTrim = String(friendly || '').trim()
  const serverTrim = String(serverRaw || '').trim()
  const serverExtra = serverTrim && serverTrim !== friendlyTrim ? serverTrim : ''

  let title = 'Có lỗi'
  let networkHint = ''

  if (!err?.response) {
    const code = err?.code
    const msg = err?.message || ''
    title = 'Lỗi kết nối'
    if (code === 'ECONNABORTED' || /timeout/i.test(msg)) {
      networkHint = 'Hết thời gian chờ phản hồi. Thử lại sau vài giây.'
    } else if (code === 'ERR_NETWORK' || /network/i.test(msg)) {
      networkHint =
        'Không nhận được phản hồi từ máy chủ (ERR_NETWORK). Kiểm tra mạng/VPN, HTTPS, hoặc cấu hình reverse proxy /api. Nếu trang chạy HTTPS mà API là HTTP, trình duyệt sẽ chặn (mixed content).'
    } else if (!code && !msg) {
      networkHint = 'Không có chi tiết từ trình duyệt. Thử làm mới trang hoặc đăng nhập lại.'
    }
  } else if (status === 429) {
    title = 'Quá nhiều yêu cầu (429 Too Many Requests)'
  } else if (status === 403) {
    title = 'Không có quyền (403)'
  } else if (status === 404) {
    title = 'Không tìm thấy (404)'
  } else if (status === 422) {
    title = 'Dữ liệu không hợp lệ (422)'
  } else if (status != null && status >= 500) {
    title = `Lỗi máy chủ (${status})`
  } else if (status != null) {
    title = `Lỗi HTTP ${status}`
  }

  const details = {
    status,
    method,
    path,
    retryAfter,
    serverRaw: serverExtra,
    networkHint,
    axiosCode: axiosCode || null,
    axiosMessage: axiosMessage || null,
    // Chỉ khi không có cả config axios và không có message (lỗi “trống”) mới gợi ý kiểm tra /api.
    configMissing: Boolean(!cfg && !(typeof err?.message === 'string' && err.message.trim())),
  }

  return { title, friendly, details }
}
