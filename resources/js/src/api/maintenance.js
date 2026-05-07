import { http } from './http'

/** GET /driver/maintenance — list all items + reminders for current driver's vehicle */
export async function getMaintenanceList() {
  const { data } = await http.get('/driver/maintenance')
  return data.data
}

/** GET /driver/maintenance/:id — detail with images + history */
export async function getMaintenanceItem(id) {
  const { data } = await http.get(`/driver/maintenance/${id}`)
  return data.data
}

/**
 * PUT /driver/maintenance/:id
 * @param {number} id
 * @param {object} payload
 */
export async function updateMaintenanceItem(id, payload) {
  const { data } = await http.put(`/driver/maintenance/${id}`, payload)
  return data.data
}

/**
 * POST /driver/maintenance/:id/images
 * @param {number} id
 * @param {File} file
 * @param {(pct: number) => void} [onProgress]
 */
export async function uploadMaintenanceImage(id, file, onProgress) {
  const fd = new FormData()
  fd.append('image', file)
  const { data } = await http.post(`/driver/maintenance/${id}/images`, fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}

/**
 * POST /driver/maintenance/reminders
 * @param {{ title: string, repeat_type: string, remind_at: string }} payload
 */
export async function createMaintenanceReminder(payload) {
  const { data } = await http.post('/driver/maintenance/reminders', payload)
  return data.data
}

/** DELETE /driver/maintenance/reminders/:id */
export async function deleteMaintenanceReminder(id) {
  const { data } = await http.delete(`/driver/maintenance/reminders/${id}`)
  return data.data
}
