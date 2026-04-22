import axios from 'axios'
import { APP_CONFIG } from '../config/appConfig'

/**
 * URL tuyệt đối cho replay outbox / logging.
 * @param {import('axios').InternalAxiosRequestConfig} config
 */
export function buildAbsoluteApiUrl(config) {
  const path = axios.getUri({
    baseURL: config.baseURL || APP_CONFIG.apiBase,
    url: config.url || '',
    params: config.params,
    paramsSerializer: config.paramsSerializer,
  })
  if (path.startsWith('http')) return path
  if (typeof window !== 'undefined') {
    const p = path.startsWith('/') ? path : `/${path}`
    return `${window.location.origin}${p}`
  }
  return path
}
