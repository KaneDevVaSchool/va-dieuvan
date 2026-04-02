import { http } from './http'

export function listRequests(params) {
  return http.get('/requests', { params })
}

