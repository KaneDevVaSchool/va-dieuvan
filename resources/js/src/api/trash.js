import { http } from './http'

/**
 * @returns {Promise<{ counts: Record<string, number> }>}
 */
export async function getTrashSummary() {
  const { data } = await http.get('/trash/summary')
  return data.data
}

/**
 * @param {{ type?: string, q?: string, page?: number, per_page?: number }} params
 * @returns {Promise<{ items: TrashItem[], meta: PaginationMeta }>}
 */
export async function listTrash(params = {}) {
  const { data } = await http.get('/trash', { params })
  return data.data
}

/**
 * @param {{ type?: string, ids?: number[], groups?: { type: string, ids: number[] }[] }} payload
 * @returns {Promise<{ restored: number }>}
 */
export async function restoreTrashItems(payload) {
  const body = payload.groups
    ? { groups: payload.groups }
    : { type: payload.type, ids: payload.ids }
  const { data } = await http.post('/trash/restore', body)
  return data.data
}

/**
 * @param {{ type?: string, ids?: number[], groups?: { type: string, ids: number[] }[] }} payload
 * @returns {Promise<{ deleted: number }>}
 */
export async function forceDeleteTrashItems(payload) {
  const body = payload.groups
    ? { groups: payload.groups }
    : { type: payload.type, ids: payload.ids }
  const { data } = await http.post('/trash/force-delete', body)
  return data.data
}
