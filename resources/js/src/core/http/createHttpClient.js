import axios from 'axios'
import { TOKEN_KEY } from '../config/authKeys'
import { APP_CONFIG } from '../config/appConfig'

/**
 * Client HTTP thuần — interceptors nghiệp vụ (401, outbox) gắn tại {@link ../../api/http.js}.
 */
export function createHttpClient() {
  const instance = axios.create({
    baseURL: APP_CONFIG.apiBase,
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
    },
  })

  instance.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  })

  return instance
}
