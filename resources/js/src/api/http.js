import axios from 'axios'

const TOKEN_KEY = 'sanctum_token'

export const http = axios.create({
  baseURL: '/api',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (r) => r,
  (err) => {
    if (err.response?.status === 401 && !err.config?.url?.includes('/login')) {
      localStorage.removeItem(TOKEN_KEY)
      if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname + window.location.search)}`
      }
    }
    return Promise.reject(err)
  },
)

export { TOKEN_KEY }

/** @param {unknown} err Axios-like error */
export function formatApiError(err, fallback = 'Có lỗi xảy ra.') {
  const d = err?.response?.data
  if (!d) return fallback
  if (typeof d.message === 'string' && d.message) return d.message
  if (d.errors && typeof d.errors === 'object') {
    const first = Object.values(d.errors).flat()[0]
    if (first != null) return Array.isArray(first) ? first[0] : String(first)
  }
  return fallback
}
